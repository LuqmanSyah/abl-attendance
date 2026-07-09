<?php

namespace App\Filament\Resources\DutyAssignments\Pages;

use App\Filament\Concerns\NotifiesValidationFailures;
use App\Filament\Resources\DutyAssignments\DutyAssignmentResource;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateDutyAssignment extends CreateRecord
{
    use NotifiesValidationFailures;

    protected static string $resource = DutyAssignmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $this->ensureDutyAssignmentDataIsValid($data);
        } catch (ValidationException $exception) {
            $this->notifyValidationFailure($exception);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function ensureDutyAssignmentDataIsValid(array $data): void
    {
        if (Carbon::parse($data['ends_at'])->lte(Carbon::parse($data['starts_at']))) {
            throw ValidationException::withMessages([
                'ends_at' => 'Waktu selesai dinas harus setelah waktu mulai dinas.',
            ]);
        }

        $employee = Employee::query()->find($data['employee_id'] ?? null);

        if (! $employee || (int) $employee->superior_id !== (int) ($data['supervisor_id'] ?? 0)) {
            throw ValidationException::withMessages([
                'supervisor_id' => 'Atasan penugasan harus sesuai dengan atasan pegawai.',
            ]);
        }
    }
}
