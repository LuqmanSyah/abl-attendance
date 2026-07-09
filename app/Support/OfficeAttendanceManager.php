<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\DutyAssignment;
use App\Models\Employee;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class OfficeAttendanceManager
{
    public function checkIn(
        Employee $employee,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?CarbonInterface $at = null,
    ): AttendanceRecord {
        $at ??= now();
        $this->ensureCoordinatesAreValid($latitude, $longitude);
        $this->ensureEmployeeIsNotOnActiveDutyAssignment($employee, $at);

        $record = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $at->toDateString())
            ->first();

        $record ??= new AttendanceRecord([
            'employee_id' => $employee->id,
            'attendance_date' => $at->toDateString(),
        ]);

        if ($record->exists && $record->attendance_type !== 'office') {
            throw ValidationException::withMessages([
                'attendance' => 'Pegawai sudah memiliki absensi lain untuk tanggal ini.',
            ]);
        }

        if ($record->exists && filled($record->check_in_at)) {
            throw ValidationException::withMessages([
                'check_in' => 'Pegawai sudah melakukan absen masuk hari ini.',
            ]);
        }

        [$distanceMeters, $locationStatus] = $this->resolveOfficeLocation($latitude, $longitude);

        $record->fill([
            'attendance_type' => 'office',
            'duty_assignment_id' => null,
            'check_in_at' => $at,
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'check_in_accuracy' => $accuracy,
            'check_in_distance_meters' => $distanceMeters,
            'check_in_location_status' => $locationStatus,
            'status' => 'present',
            'verification_status' => 'approved',
        ]);

        $record->save();

        return $record;
    }

    public function checkOut(
        Employee $employee,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?CarbonInterface $at = null,
    ): AttendanceRecord {
        $at ??= now();
        $this->ensureCoordinatesAreValid($latitude, $longitude);
        $this->ensureEmployeeIsNotOnActiveDutyAssignment($employee, $at);

        $record = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $at->toDateString())
            ->where('attendance_type', 'office')
            ->first();

        if (! $record || blank($record->check_in_at)) {
            throw ValidationException::withMessages([
                'check_out' => 'Absen masuk harus dilakukan sebelum absen pulang.',
            ]);
        }

        if ($record->check_in_at->gt($at)) {
            throw ValidationException::withMessages([
                'check_out' => 'Absen pulang harus dilakukan setelah absen masuk.',
            ]);
        }

        if (filled($record->check_out_at)) {
            throw ValidationException::withMessages([
                'check_out' => 'Pegawai sudah melakukan absen pulang hari ini.',
            ]);
        }

        [$distanceMeters, $locationStatus] = $this->resolveOfficeLocation($latitude, $longitude);

        $record->fill([
            'check_out_at' => $at,
            'check_out_latitude' => $latitude,
            'check_out_longitude' => $longitude,
            'check_out_accuracy' => $accuracy,
            'check_out_distance_meters' => $distanceMeters,
            'check_out_location_status' => $locationStatus,
            'verification_status' => 'approved',
        ]);

        $record->save();

        return $record;
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    protected function resolveOfficeLocation(float $latitude, float $longitude): array
    {
        $officeLatitude = config('attendance.office.latitude');
        $officeLongitude = config('attendance.office.longitude');
        $radiusMeters = (int) config('attendance.office.radius_meters', 100);

        if (! is_numeric($officeLatitude) || ! is_numeric($officeLongitude)) {
            throw ValidationException::withMessages([
                'office_location' => 'Koordinat kantor belum diatur.',
            ]);
        }

        $distanceMeters = GeoDistance::meters(
            $latitude,
            $longitude,
            (float) $officeLatitude,
            (float) $officeLongitude,
        );

        return [
            $distanceMeters,
            GeoDistance::locationStatus($distanceMeters, $radiusMeters),
        ];
    }

    protected function ensureEmployeeIsNotOnActiveDutyAssignment(Employee $employee, CarbonInterface $at): void
    {
        $hasActiveDutyAssignment = DutyAssignment::query()
            ->where('employee_id', $employee->id)
            ->activeAt($at)
            ->exists();

        if ($hasActiveDutyAssignment) {
            throw ValidationException::withMessages([
                'attendance' => 'Pegawai sedang memiliki penugasan dinas aktif. Gunakan Absensi Dinas.',
            ]);
        }
    }

    protected function ensureCoordinatesAreValid(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw ValidationException::withMessages([
                'latitude' => 'Latitude harus berada di antara -90 dan 90.',
            ]);
        }

        if ($longitude < -180 || $longitude > 180) {
            throw ValidationException::withMessages([
                'longitude' => 'Longitude harus berada di antara -180 dan 180.',
            ]);
        }
    }
}
