<?php

namespace Tests\Feature;

use App\Models\AttendanceSetting;
use App\Models\Division;
use App\Models\DutyAssignment;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Support\FaceRecognitionService;
use App\Support\OfficeAttendanceManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OfficeAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private const FACE_IMAGE = 'data:image/jpeg;base64,ZmFrZQ==';

    public function test_employee_can_check_in_for_office_attendance(): void
    {
        $this->configureOfficeLocation();

        $employee = $this->createEmployee();

        $record = app(OfficeAttendanceManager::class)->checkIn(
            $employee,
            -6.2005000,
            106.8166667,
            10.5,
            at: Carbon::parse('2026-07-02 08:00:00'),
            faceImage: self::FACE_IMAGE,
        );

        $this->assertSame('office', $record->attendance_type);
        $this->assertSame('approved', $record->verification_status);
        $this->assertSame('present', $record->status);
        $this->assertSame('inside_radius', $record->check_in_location_status);
        $this->assertSame(0.25, $record->check_in_face_distance);
        $this->assertNotNull($record->check_in_face_verified_at);
        $this->assertSame('2026-07-02', $record->attendance_date->toDateString());
    }

    public function test_employee_can_check_out_after_office_check_in(): void
    {
        $this->configureOfficeLocation();

        $employee = $this->createEmployee();

        app(OfficeAttendanceManager::class)->checkIn(
            $employee,
            -6.2005000,
            106.8166667,
            null,
            at: Carbon::parse('2026-07-02 08:00:00'),
            faceImage: self::FACE_IMAGE,
        );

        $record = app(OfficeAttendanceManager::class)->checkOut(
            $employee,
            -6.2004000,
            106.8166667,
            null,
            at: Carbon::parse('2026-07-02 17:00:00'),
            faceImage: self::FACE_IMAGE,
        );

        $this->assertNotNull($record->check_out_at);
        $this->assertSame('inside_radius', $record->check_out_location_status);
        $this->assertSame(0.25, $record->check_out_face_distance);
        $this->assertNotNull($record->check_out_face_verified_at);
    }

    public function test_employee_cannot_check_out_before_office_check_in(): void
    {
        $this->expectException(ValidationException::class);

        app(OfficeAttendanceManager::class)->checkOut(
            $this->createEmployee(),
            -6.2005000,
            106.8166667,
            null,
            Carbon::parse('2026-07-02 17:00:00'),
        );
    }

    public function test_employee_cannot_check_in_twice_for_office_attendance(): void
    {
        $this->configureOfficeLocation();

        $employee = $this->createEmployee();

        app(OfficeAttendanceManager::class)->checkIn(
            $employee,
            -6.2005000,
            106.8166667,
            null,
            at: Carbon::parse('2026-07-02 08:00:00'),
            faceImage: self::FACE_IMAGE,
        );

        $this->expectException(ValidationException::class);

        app(OfficeAttendanceManager::class)->checkIn(
            $employee,
            -6.2005000,
            106.8166667,
            null,
            Carbon::parse('2026-07-02 09:00:00'),
        );
    }

    public function test_employee_cannot_check_in_without_registered_face(): void
    {
        $this->configureOfficeLocation();

        $employee = $this->createEmployee(faceEmbedding: null);

        $this->expectException(ValidationException::class);

        app(OfficeAttendanceManager::class)->checkIn(
            $employee,
            -6.2005000,
            106.8166667,
            null,
            at: Carbon::parse('2026-07-02 08:00:00'),
            faceImage: self::FACE_IMAGE,
        );
    }

    public function test_employee_cannot_check_in_outside_office_radius(): void
    {
        $this->configureOfficeLocation();

        $this->expectException(ValidationException::class);

        app(OfficeAttendanceManager::class)->checkIn(
            $this->createEmployee(),
            -6.2500000,
            106.8166667,
            null,
            at: Carbon::parse('2026-07-02 08:00:00'),
            faceImage: self::FACE_IMAGE,
        );
    }

    public function test_employee_cannot_check_in_when_face_does_not_match(): void
    {
        $this->configureOfficeLocation();
        $this->mock(FaceRecognitionService::class)
            ->shouldReceive('verify')
            ->once()
            ->andReturn([
                'matched' => false,
                'distance' => 0.71,
                'tolerance' => 0.5,
            ]);

        $this->expectException(ValidationException::class);

        app(OfficeAttendanceManager::class)->checkIn(
            $this->createEmployee(),
            -6.2005000,
            106.8166667,
            null,
            at: Carbon::parse('2026-07-02 08:00:00'),
            faceImage: self::FACE_IMAGE,
        );
    }

    public function test_office_attendance_page_renders_for_employee(): void
    {
        $employee = $this->createEmployee();

        $this->actingAs($employee->user)
            ->get('/pegawai/absensi')
            ->assertOk();
    }

    public function test_office_attendance_page_does_not_require_face_when_face_recognition_is_disabled(): void
    {
        $this->configureOfficeLocation();
        Config::set('attendance.face.enabled', false);

        $employee = $this->createEmployee(faceEmbedding: null);

        $this->actingAs($employee->user)
            ->get('/pegawai/absensi')
            ->assertOk()
            ->assertDontSee('Foto wajah Anda belum terdaftar')
            ->assertDontSee('Nyalakan Kamera')
            ->assertSee('Absen Masuk');
    }

    public function test_employee_cannot_check_in_when_office_location_is_not_configured(): void
    {
        Config::set('attendance.office.latitude', null);
        Config::set('attendance.office.longitude', null);

        $this->expectException(ValidationException::class);

        app(OfficeAttendanceManager::class)->checkIn(
            $this->createEmployee(),
            -6.2005000,
            106.8166667,
            null,
            Carbon::parse('2026-07-02 08:00:00'),
        );
    }

    public function test_office_attendance_uses_database_office_location_settings(): void
    {
        Config::set('attendance.office.latitude', null);
        Config::set('attendance.office.longitude', null);
        Config::set('attendance.face.enabled', true);
        $this->fakeFaceVerification();

        AttendanceSetting::create([
            'office_latitude' => -6.2000000,
            'office_longitude' => 106.8166667,
            'office_radius_meters' => 100,
        ]);

        $record = app(OfficeAttendanceManager::class)->checkIn(
            $this->createEmployee(),
            -6.2005000,
            106.8166667,
            null,
            at: Carbon::parse('2026-07-02 08:00:00'),
            faceImage: self::FACE_IMAGE,
        );

        $this->assertSame('inside_radius', $record->check_in_location_status);
    }

    public function test_office_attendance_page_points_employee_to_duty_attendance_when_on_active_assignment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-02 09:00:00'));
        Config::set('attendance.office.latitude', null);
        Config::set('attendance.office.longitude', null);

        $supervisor = $this->createSupervisor();
        $employee = $this->createEmployee();

        DutyAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_id' => $supervisor->id,
            'title' => 'Dinas Luar Kota',
            'location_name' => 'Bandung',
            'latitude' => -6.9175000,
            'longitude' => 107.6191000,
            'radius_meters' => 100,
            'starts_at' => Carbon::parse('2026-07-02 08:00:00'),
            'ends_at' => Carbon::parse('2026-07-02 17:00:00'),
            'status' => 'active',
        ]);

        try {
            $this->actingAs($employee->user)
                ->get('/pegawai/absensi')
                ->assertOk()
                ->assertSee('penugasan dinas aktif')
                ->assertSee('Buka Absensi Dinas')
                ->assertDontSee('Koordinat kantor belum diatur');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_employee_cannot_check_in_for_office_attendance_when_on_active_duty_assignment(): void
    {
        $this->configureOfficeLocation();

        $supervisor = $this->createSupervisor();
        $employee = $this->createEmployee();

        DutyAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_id' => $supervisor->id,
            'title' => 'Dinas Luar Kota',
            'location_name' => 'Bandung',
            'latitude' => -6.9175000,
            'longitude' => 107.6191000,
            'radius_meters' => 100,
            'starts_at' => Carbon::parse('2026-07-02 08:00:00'),
            'ends_at' => Carbon::parse('2026-07-02 17:00:00'),
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        app(OfficeAttendanceManager::class)->checkIn(
            $employee,
            -6.2005000,
            106.8166667,
            null,
            Carbon::parse('2026-07-02 09:00:00'),
        );
    }

    protected function configureOfficeLocation(): void
    {
        Config::set('attendance.office.latitude', -6.2000000);
        Config::set('attendance.office.longitude', 106.8166667);
        Config::set('attendance.office.radius_meters', 100);
        Config::set('attendance.face.enabled', true);

        $this->fakeFaceVerification();
    }

    protected function fakeFaceVerification(bool $matched = true, float $distance = 0.25): void
    {
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'matched' => $matched,
                'distance' => $distance,
                'tolerance' => 0.5,
            ]),
        ]);
    }

    protected function createSupervisor(): Employee
    {
        $division = Division::firstOrCreate(['name' => 'Operasional']);
        $position = Position::firstOrCreate(
            ['name' => 'Atasan'],
            ['requires_superior' => false, 'can_be_superior' => true],
        );

        $user = User::factory()->create([
            'name' => 'Atasan Satu',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'supervisor',
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'division_id' => $division->id,
            'position_id' => $position->id,
            'employee_code' => fake()->unique()->bothify('SPV###'),
            'name' => $user->name,
            'status' => 'active',
        ]);
    }

    protected function createEmployee(?array $faceEmbedding = [0.1, 0.2, 0.3]): Employee
    {
        $division = Division::firstOrCreate(['name' => 'Operasional']);
        $position = Position::firstOrCreate(
            ['name' => 'Pegawai'],
            ['requires_superior' => true, 'can_be_superior' => false],
        );

        $user = User::factory()->create([
            'name' => 'Pegawai Satu',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'employee',
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'division_id' => $division->id,
            'position_id' => $position->id,
            'employee_code' => fake()->unique()->bothify('EMP###'),
            'name' => $user->name,
            'face_embedding' => $faceEmbedding,
            'face_registered_at' => filled($faceEmbedding) ? now() : null,
            'status' => 'active',
        ]);
    }
}
