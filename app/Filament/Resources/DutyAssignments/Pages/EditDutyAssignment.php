<?php

namespace App\Filament\Resources\DutyAssignments\Pages;

use App\Filament\Resources\DutyAssignments\DutyAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDutyAssignment extends EditRecord
{
    protected static string $resource = DutyAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus'),
        ];
    }
}
