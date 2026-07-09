<?php

namespace App\Filament\Resources\AttendanceRecords\Pages;

use App\Filament\Concerns\NotifiesValidationFailures;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Support\AttendanceRecordDataNormalizer;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAttendanceRecord extends CreateRecord
{
    use NotifiesValidationFailures;

    protected static string $resource = AttendanceRecordResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            return app(AttendanceRecordDataNormalizer::class)->normalize($data);
        } catch (ValidationException $exception) {
            $this->notifyValidationFailure($exception);
        }
    }
}
