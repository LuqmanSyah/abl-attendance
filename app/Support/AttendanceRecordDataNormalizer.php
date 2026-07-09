<?php

namespace App\Support;

use App\Models\DutyAssignment;
use Illuminate\Validation\ValidationException;

class AttendanceRecordDataNormalizer
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalize(array $data): array
    {
        $this->ensureCoordinatePairIsValid($data, 'check_in');
        $this->ensureCoordinatePairIsValid($data, 'check_out');

        if (($data['attendance_type'] ?? null) !== 'duty') {
            $data['duty_assignment_id'] = null;

            return $data;
        }

        $assignment = DutyAssignment::query()->find($data['duty_assignment_id'] ?? null);

        if (! $assignment) {
            throw ValidationException::withMessages([
                'duty_assignment_id' => 'Penugasan dinas wajib dipilih untuk absensi dinas.',
            ]);
        }

        if ((int) $assignment->employee_id !== (int) ($data['employee_id'] ?? 0)) {
            throw ValidationException::withMessages([
                'duty_assignment_id' => 'Penugasan dinas harus sesuai dengan pegawai absensi.',
            ]);
        }

        return $this->fillDutyLocationData($data, $assignment);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function ensureCoordinatePairIsValid(array $data, string $prefix): void
    {
        $latitude = $data["{$prefix}_latitude"] ?? null;
        $longitude = $data["{$prefix}_longitude"] ?? null;

        if (blank($latitude) && blank($longitude)) {
            return;
        }

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            throw ValidationException::withMessages([
                "{$prefix}_latitude" => 'Latitude dan longitude harus diisi berpasangan.',
            ]);
        }

        if ((float) $latitude < -90 || (float) $latitude > 90) {
            throw ValidationException::withMessages([
                "{$prefix}_latitude" => 'Latitude harus berada di antara -90 dan 90.',
            ]);
        }

        if ((float) $longitude < -180 || (float) $longitude > 180) {
            throw ValidationException::withMessages([
                "{$prefix}_longitude" => 'Longitude harus berada di antara -180 dan 180.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillDutyLocationData(array $data, DutyAssignment $assignment): array
    {
        foreach (['check_in', 'check_out'] as $prefix) {
            if (blank($data["{$prefix}_latitude"] ?? null) || blank($data["{$prefix}_longitude"] ?? null)) {
                continue;
            }

            $distanceMeters = GeoDistance::meters(
                (float) $data["{$prefix}_latitude"],
                (float) $data["{$prefix}_longitude"],
                $assignment->latitude,
                $assignment->longitude,
            );

            $data["{$prefix}_distance_meters"] = $distanceMeters;
            $data["{$prefix}_location_status"] = GeoDistance::locationStatus($distanceMeters, $assignment->radius_meters);
        }

        return $data;
    }
}
