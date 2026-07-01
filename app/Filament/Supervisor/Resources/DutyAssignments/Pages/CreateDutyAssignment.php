<?php

namespace App\Filament\Supervisor\Resources\DutyAssignments\Pages;

use App\Filament\Supervisor\Resources\DutyAssignments\DutyAssignmentResource;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateDutyAssignment extends CreateRecord
{
    protected static string $resource = DutyAssignmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
                'employee_id' => 'Atasan hanya dapat membuat penugasan untuk bawahannya.',
            ]);
        }

        $data['supervisor_id'] = $supervisor->id;

        return $data;
    }
}
