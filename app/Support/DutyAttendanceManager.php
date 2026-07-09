<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\DutyAssignment;
use App\Models\Employee;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class DutyAttendanceManager
{
    public function checkIn(
        Employee $employee,
        DutyAssignment $assignment,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?CarbonInterface $at = null,
    ): AttendanceRecord {
        $at ??= now();
        $this->ensureCoordinatesAreValid($latitude, $longitude);
        $this->ensureAssignmentCanBeUsedByEmployee($employee, $assignment, $at);

        $record = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $at->toDateString())
            ->first();

        $record ??= new AttendanceRecord([
            'employee_id' => $employee->id,
            'attendance_date' => $at->toDateString(),
        ]);

        if ($record->exists && (int) $record->duty_assignment_id !== $assignment->id) {
            throw ValidationException::withMessages([
                'assignment' => 'Pegawai sudah memiliki absensi untuk tanggal ini.',
            ]);
        }

        if ($record->exists && filled($record->check_in_at)) {
            throw ValidationException::withMessages([
                'check_in' => 'Pegawai sudah melakukan absen masuk untuk penugasan ini.',
            ]);
        }

        $distanceMeters = GeoDistance::meters(
            $latitude,
            $longitude,
            $assignment->latitude,
            $assignment->longitude,
        );

        $record->fill([
            'attendance_type' => 'duty',
            'duty_assignment_id' => $assignment->id,
            'check_in_at' => $at,
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'check_in_accuracy' => $accuracy,
            'check_in_distance_meters' => $distanceMeters,
            'check_in_location_status' => GeoDistance::locationStatus($distanceMeters, $assignment->radius_meters),
            'status' => 'present',
            'verification_status' => 'pending',
        ]);

        $record->save();

        return $record;
    }

    public function checkOut(
        Employee $employee,
        DutyAssignment $assignment,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?CarbonInterface $at = null,
    ): AttendanceRecord {
        $at ??= now();
        $this->ensureCoordinatesAreValid($latitude, $longitude);
        $this->ensureAssignmentCanBeUsedByEmployee($employee, $assignment, $at);

        $record = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $at->toDateString())
            ->where('duty_assignment_id', $assignment->id)
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
                'check_out' => 'Pegawai sudah melakukan absen pulang untuk penugasan ini.',
            ]);
        }

        $distanceMeters = GeoDistance::meters(
            $latitude,
            $longitude,
            $assignment->latitude,
            $assignment->longitude,
        );

        $record->fill([
            'check_out_at' => $at,
            'check_out_latitude' => $latitude,
            'check_out_longitude' => $longitude,
            'check_out_accuracy' => $accuracy,
            'check_out_distance_meters' => $distanceMeters,
            'check_out_location_status' => GeoDistance::locationStatus($distanceMeters, $assignment->radius_meters),
            'verification_status' => 'pending',
        ]);

        $record->save();

        return $record;
    }

    public function verify(
        AttendanceRecord $record,
        Employee $supervisor,
        string $status,
        ?string $notes = null,
        ?CarbonInterface $at = null,
    ): AttendanceRecord {
        $at ??= now();

        if (! in_array($status, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'verification_status' => 'Status verifikasi tidak valid.',
            ]);
        }

        if ((int) $record->employee?->superior_id !== $supervisor->id) {
            throw ValidationException::withMessages([
                'record' => 'Atasan hanya dapat memverifikasi absensi bawahannya.',
            ]);
        }

        if (blank($supervisor->user_id)) {
            throw ValidationException::withMessages([
                'supervisor' => 'Atasan belum memiliki akun pengguna.',
            ]);
        }

        $record->fill([
            'verification_status' => $status,
            'verified_by' => $supervisor->user_id,
            'verified_at' => $at,
            'verification_notes' => $notes,
        ]);

        $record->save();

        return $record;
    }

    protected function ensureAssignmentCanBeUsedByEmployee(Employee $employee, DutyAssignment $assignment, CarbonInterface $at): void
    {
        if ((int) $assignment->employee_id !== $employee->id) {
            throw ValidationException::withMessages([
                'assignment' => 'Penugasan dinas tidak terdaftar untuk pegawai ini.',
            ]);
        }

        if ($assignment->status !== 'active' || $assignment->starts_at->gt($at) || $assignment->ends_at->lt($at)) {
            throw ValidationException::withMessages([
                'assignment' => 'Penugasan dinas tidak aktif pada waktu absensi.',
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
