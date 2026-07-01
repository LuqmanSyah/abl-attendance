<?php

namespace App\Filament\Resources\DutyAssignments\Pages;

use App\Filament\Resources\DutyAssignments\DutyAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDutyAssignments extends ListRecords
{
    protected static string $resource = DutyAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
