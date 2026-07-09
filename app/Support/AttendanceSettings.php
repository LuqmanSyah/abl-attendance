<?php

namespace App\Support;

use App\Models\AttendanceSetting;

class AttendanceSettings
{
    public function officeLatitude(): mixed
    {
        return $this->record()?->office_latitude ?? config('attendance.office.latitude');
    }

    public function officeLongitude(): mixed
    {
        return $this->record()?->office_longitude ?? config('attendance.office.longitude');
    }

    public function officeRadiusMeters(): int
    {
        return (int) ($this->record()?->office_radius_meters ?? config('attendance.office.radius_meters', 100));
    }

    public function officeLocationConfigured(): bool
    {
        return is_numeric($this->officeLatitude()) && is_numeric($this->officeLongitude());
    }

    public function saveOfficeLocation(float $latitude, float $longitude, int $radiusMeters): AttendanceSetting
    {
        $setting = AttendanceSetting::query()->firstOrNew();

        $setting->fill([
            'office_latitude' => $latitude,
            'office_longitude' => $longitude,
            'office_radius_meters' => $radiusMeters,
        ]);

        $setting->save();

        return $setting;
    }

    protected function record(): ?AttendanceSetting
    {
        return AttendanceSetting::query()->first();
    }
}
