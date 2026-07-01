<?php

namespace App\Filament\Employee\Pages;

use App\Models\DutyAssignment;
use App\Support\DutyAttendanceManager;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class DutyAttendance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?string $navigationLabel = 'Absensi Dinas';

    protected static ?string $title = 'Absensi Dinas';

    protected static ?string $slug = 'absensi-dinas';

    protected string $view = 'filament.employee.pages.duty-attendance';

    /**
     * @return Collection<int, DutyAssignment>
     */
    public function getActiveAssignmentsProperty(): Collection
    {
        $employee = Filament::auth()->user()?->employee;

        if (! $employee) {
            return new Collection;
        }

        return DutyAssignment::query()
            ->with(['attendanceRecords' => fn ($query) => $query
                ->where('employee_id', $employee->id)
                ->where('attendance_date', now()->toDateString())])
            ->where('employee_id', $employee->id)
            ->activeAt()
            ->orderBy('starts_at')
            ->get();
    }

    public function checkIn(int $assignmentId, float $latitude, float $longitude, ?float $accuracy = null): void
    {
        $this->recordAttendance($assignmentId, $latitude, $longitude, $accuracy, 'checkIn');
    }

    public function checkOut(int $assignmentId, float $latitude, float $longitude, ?float $accuracy = null): void
    {
        $this->recordAttendance($assignmentId, $latitude, $longitude, $accuracy, 'checkOut');
    }

    protected function recordAttendance(
        int $assignmentId,
        float $latitude,
        float $longitude,
        ?float $accuracy,
        string $method,
    ): void {
        $employee = Filament::auth()->user()?->employee;

        if (! $employee) {
            Notification::make()
                ->title('Akun belum terhubung dengan data pegawai')
                ->danger()
                ->send();

            return;
        }

        $assignment = DutyAssignment::query()
            ->whereKey($assignmentId)
            ->where('employee_id', $employee->id)
            ->first();

        if (! $assignment) {
            Notification::make()
                ->title('Penugasan dinas tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        try {
            app(DutyAttendanceManager::class)->{$method}(
                $employee,
                $assignment,
                $latitude,
                $longitude,
                $accuracy,
            );

            Notification::make()
                ->title($method === 'checkIn' ? 'Absen masuk dinas tersimpan' : 'Absen pulang dinas tersimpan')
                ->success()
                ->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first() ?: 'Absensi dinas gagal disimpan')
                ->danger()
                ->send();
        }
    }
}
