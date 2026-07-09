<?php

namespace App\Filament\Employee\Pages;

use App\Models\AttendanceRecord;
use App\Models\DutyAssignment;
use App\Support\OfficeAttendanceManager;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class OfficeAttendance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?string $navigationLabel = 'Absensi Kantor';

    protected static ?string $title = 'Absensi Kantor';

    protected static ?string $slug = 'absensi';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.employee.pages.office-attendance';

    public function getTodayRecordProperty(): ?AttendanceRecord
    {
        $employee = Filament::auth()->user()?->employee;

        if (! $employee) {
            return null;
        }

        return AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->where('attendance_type', 'office')
            ->whereDate('attendance_date', now()->toDateString())
            ->first();
    }

    public function getActiveDutyAssignmentProperty(): ?DutyAssignment
    {
        $employee = Filament::auth()->user()?->employee;

        if (! $employee) {
            return null;
        }

        return DutyAssignment::query()
            ->where('employee_id', $employee->id)
            ->activeAt()
            ->orderBy('starts_at')
            ->first();
    }

    public function checkIn(float $latitude, float $longitude, ?float $accuracy = null, ?string $faceImage = null): void
    {
        $this->recordAttendance($latitude, $longitude, $accuracy, 'checkIn', $faceImage);
    }

    public function checkOut(float $latitude, float $longitude, ?float $accuracy = null, ?string $faceImage = null): void
    {
        $this->recordAttendance($latitude, $longitude, $accuracy, 'checkOut', $faceImage);
    }

    protected function recordAttendance(
        float $latitude,
        float $longitude,
        ?float $accuracy,
        string $method,
        ?string $faceImage,
    ): void {
        $employee = Filament::auth()->user()?->employee;

        if (! $employee) {
            Notification::make()
                ->title('Akun belum terhubung dengan data pegawai')
                ->danger()
                ->send();

            return;
        }

        try {
            app(OfficeAttendanceManager::class)->{$method}(
                $employee,
                $latitude,
                $longitude,
                $accuracy,
                faceImage: $faceImage,
            );

            Notification::make()
                ->title($method === 'checkIn' ? 'Absen masuk tersimpan' : 'Absen pulang tersimpan')
                ->success()
                ->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first() ?: 'Absensi gagal disimpan')
                ->danger()
                ->send();
        }
    }
}
