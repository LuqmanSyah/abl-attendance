<?php

namespace App\Filament\Supervisor\Resources\DutyAssignments;

use App\Filament\Supervisor\Resources\DutyAssignments\Pages\CreateDutyAssignment;
use App\Filament\Supervisor\Resources\DutyAssignments\Pages\EditDutyAssignment;
use App\Filament\Supervisor\Resources\DutyAssignments\Pages\ListDutyAssignments;
use App\Filament\Supervisor\Resources\DutyAssignments\Schemas\DutyAssignmentForm;
use App\Filament\Supervisor\Resources\DutyAssignments\Tables\DutyAssignmentsTable;
use App\Models\DutyAssignment;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DutyAssignmentResource extends Resource
{
    protected static ?string $model = DutyAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Dinas';

    protected static ?string $navigationLabel = 'Penugasan Dinas';

    protected static ?string $modelLabel = 'penugasan dinas';

    protected static ?string $pluralModelLabel = 'penugasan dinas';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return DutyAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DutyAssignmentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $supervisorId = Filament::auth()->user()?->employee?->id;

        return parent::getEloquentQuery()
            ->where('supervisor_id', $supervisorId);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDutyAssignments::route('/'),
            'create' => CreateDutyAssignment::route('/create'),
            'edit' => EditDutyAssignment::route('/{record}/edit'),
        ];
    }
}
