<?php

namespace App\Filament\Supervisor\Resources\DutyAssignments\Pages;

use App\Filament\Concerns\NotifiesValidationFailures;
use App\Filament\Supervisor\Resources\DutyAssignments\DutyAssignmentResource;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EditDutyAssignment extends EditRecord
{
    use NotifiesValidationFailures;

    protected static string $resource = DutyAssignmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            $supervisor = Filament::auth()->user()?->employee;

            if (! $supervisor) {
                throw ValidationException::withMessages([
                    'supervisor_id' => 'Akun atasan belum terhubung dengan data pegawai.',
                ]);
            }

            if (! Employee::query()
                ->whereKey($data['employee_id'] ?? null)
                ->where('superior_id', $supervisor->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Atasan hanya dapat mengubah penugasan untuk bawahannya.',
                ]);
            }

            $this->ensureScheduleIsValid($data);

            $data['supervisor_id'] = $supervisor->id;
        } catch (ValidationException $exception) {
            $this->notifyValidationFailure($exception);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function ensureScheduleIsValid(array $data): void
    {
        if (Carbon::parse($data['ends_at'])->lte(Carbon::parse($data['starts_at']))) {
            throw ValidationException::withMessages([
                'ends_at' => 'Waktu selesai dinas harus setelah waktu mulai dinas.',
            ]);
        }
    }
}
