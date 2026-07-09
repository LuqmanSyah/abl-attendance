<?php

namespace App\Filament\Resources\AttendanceRecords\Pages;

use App\Filament\Concerns\NotifiesValidationFailures;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Support\AttendanceRecordDataNormalizer;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAttendanceRecord extends EditRecord
{
    use NotifiesValidationFailures;

    protected static string $resource = AttendanceRecordResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            return app(AttendanceRecordDataNormalizer::class)->normalize($data);
        } catch (ValidationException $exception) {
            $this->notifyValidationFailure($exception);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
