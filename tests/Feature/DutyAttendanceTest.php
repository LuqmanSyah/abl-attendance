<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\DutyAssignment;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Support\DutyAttendanceManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DutyAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_check_in_for_active_duty_assignment(): void
    {
        [$supervisor, $employee] = $this->createSupervisorAndEmployee();

        $assignment = DutyAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_id' => $supervisor->id,
            'title' => 'Survey Lokasi',
            'location_name' => 'Kantor Klien',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius_meters' => 100,
            'starts_at' => Carbon::parse('2026-07-02 08:00:00'),
            'ends_at' => Carbon::parse('2026-07-02 17:00:00'),
            'status' => 'active',
        ]);

        $record = app(DutyAttendanceManager::class)->checkIn(
            $employee,
            $assignment,
            -6.2005000,
            106.8166667,
            12.5,
            Carbon::parse('2026-07-02 09:00:00'),
        );

        $this->assertSame('duty', $record->attendance_type);
        $this->assertSame('pending', $record->verification_status);
        $this->assertSame('inside_radius', $record->check_in_location_status);
        $this->assertSame($assignment->id, $record->duty_assignment_id);
    }

    public function test_employee_cannot_check_in_for_another_employee_assignment(): void
    {
        [$supervisor, $employee] = $this->createSupervisorAndEmployee();
        $otherEmployee = $this->createEmployee('EMP999', 'Other Employee', 'other.employee@example.com', $supervisor);

        $assignment = DutyAssignment::create([
            'employee_id' => $otherEmployee->id,
            'supervisor_id' => $supervisor->id,
            'title' => 'Survey Lokasi',
            'location_name' => 'Kantor Klien',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius_meters' => 100,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        app(DutyAttendanceManager::class)->checkIn(
            $employee,
            $assignment,
            -6.2005000,
            106.8166667,
        );
    }

    public function test_employee_cannot_have_two_attendance_records_on_the_same_date(): void
    {
        [$supervisor, $employee] = $this->createSupervisorAndEmployee();

        $firstAssignment = $this->createAssignment($employee, $supervisor, 'Penugasan Pertama');
        $secondAssignment = $this->createAssignment($employee, $supervisor, 'Penugasan Kedua');

        app(DutyAttendanceManager::class)->checkIn(
            $employee,
            $firstAssignment,
            -6.2005000,
            106.8166667,
            null,
            Carbon::parse('2026-07-02 09:00:00'),
        );

        $this->expectException(ValidationException::class);

        app(DutyAttendanceManager::class)->checkIn(
            $employee,
            $secondAssignment,
            -6.2005000,
            106.8166667,
            null,
            Carbon::parse('2026-07-02 10:00:00'),
        );
    }

    public function test_supervisor_can_only_verify_subordinate_attendance(): void
    {
        [$supervisor, $employee] = $this->createSupervisorAndEmployee();
        [$otherSupervisor] = $this->createSupervisorAndEmployee('SPV999', 'Other Supervisor', 'other.supervisor@example.com', 'EMP998', 'Other Staff', 'other.staff@example.com');

        $assignment = $this->createAssignment($employee, $supervisor);
        $record = app(DutyAttendanceManager::class)->checkIn(
            $employee,
            $assignment,
            -6.2005000,
            106.8166667,
            null,
            Carbon::parse('2026-07-02 09:00:00'),
        );

        app(DutyAttendanceManager::class)->verify($record, $supervisor, 'approved', 'Sesuai');

        $this->assertSame('approved', $record->refresh()->verification_status);

        $record->update([
            'verification_status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'verification_notes' => null,
        ]);

        $this->expectException(ValidationException::class);

        app(DutyAttendanceManager::class)->verify($record, $otherSupervisor, 'approved');
    }

    public function test_duty_attendance_panel_pages_render(): void
    {
        [$supervisor, $employee] = $this->createSupervisorAndEmployee();

        $this->actingAs($employee->user)
            ->get('/pegawai/absensi-dinas')
            ->assertOk();

        $this->actingAs($supervisor->user)
            ->get('/atasan/duty-assignments')
            ->assertOk();

        $this->actingAs($supervisor->user)
            ->get('/atasan/duty-attendance-records')
            ->assertOk();
    }

    public function test_duty_attendance_page_shows_today_assignment_that_is_not_active_yet(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-02 09:00:00'));

        [$supervisor, $employee] = $this->createSupervisorAndEmployee();

        DutyAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_id' => $supervisor->id,
            'title' => 'Dinas Sore',
            'location_name' => 'Kantor Klien',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius_meters' => 100,
            'starts_at' => Carbon::parse('2026-07-02 15:00:00'),
            'ends_at' => Carbon::parse('2026-07-02 17:00:00'),
            'status' => 'active',
        ]);

        try {
            $this->actingAs($employee->user)
                ->get('/pegawai/absensi-dinas')
                ->assertOk()
                ->assertSee('Dinas Sore')
                ->assertSee('Belum mulai')
                ->assertDontSee('Tidak ada penugasan dinas untuk tanggal ini.');
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array{0: Employee, 1: Employee}
     */
    protected function createSupervisorAndEmployee(
        string $supervisorCode = 'SPV001',
        string $supervisorName = 'Atasan Satu',
        string $supervisorEmail = 'atasan.satu@example.com',
        string $employeeCode = 'EMP001',
        string $employeeName = 'Pegawai Satu',
        string $employeeEmail = 'pegawai.satu@example.com',
    ): array {
        $division = Division::firstOrCreate(['name' => 'Operasional']);
        $supervisorPosition = Position::firstOrCreate(
            ['name' => 'Atasan'],
            ['requires_superior' => false, 'can_be_superior' => true],
        );
        $employeePosition = Position::firstOrCreate(
            ['name' => 'Pegawai'],
            ['requires_superior' => true, 'can_be_superior' => false],
        );

        $supervisorUser = User::factory()->create([
            'name' => $supervisorName,
            'email' => $supervisorEmail,
            'role' => 'supervisor',
        ]);

        $supervisor = Employee::create([
            'user_id' => $supervisorUser->id,
            'division_id' => $division->id,
            'position_id' => $supervisorPosition->id,
            'employee_code' => $supervisorCode,
            'name' => $supervisorName,
            'status' => 'active',
        ]);

        $employee = $this->createEmployee($employeeCode, $employeeName, $employeeEmail, $supervisor, $division, $employeePosition);

        return [$supervisor, $employee];
    }

    protected function createEmployee(
        string $code,
        string $name,
        string $email,
        Employee $supervisor,
        ?Division $division = null,
        ?Position $position = null,
    ): Employee {
        $division ??= Division::firstOrCreate(['name' => 'Operasional']);
        $position ??= Position::firstOrCreate(
            ['name' => 'Pegawai'],
            ['requires_superior' => true, 'can_be_superior' => false],
        );

        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => 'employee',
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'division_id' => $division->id,
            'position_id' => $position->id,
            'superior_id' => $supervisor->id,
            'employee_code' => $code,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    protected function createAssignment(Employee $employee, Employee $supervisor, string $title = 'Survey Lokasi'): DutyAssignment
    {
        return DutyAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_id' => $supervisor->id,
            'title' => $title,
            'location_name' => 'Kantor Klien',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius_meters' => 100,
            'starts_at' => Carbon::parse('2026-07-02 08:00:00'),
            'ends_at' => Carbon::parse('2026-07-02 17:00:00'),
            'status' => 'active',
        ]);
    }
}
