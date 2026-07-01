<?php

namespace App\Filament\Supervisor\Resources\DutyAttendanceRecords;

use App\Filament\Supervisor\Resources\DutyAttendanceRecords\Pages\ListDutyAttendanceRecords;
use App\Filament\Supervisor\Resources\DutyAttendanceRecords\Tables\DutyAttendanceRecordsTable;
use App\Models\AttendanceRecord;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DutyAttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Dinas';

    protected static ?string $navigationLabel = 'Verifikasi Absensi';

    protected static ?string $modelLabel = 'absensi dinas';

    protected static ?string $pluralModelLabel = 'absensi dinas';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return DutyAttendanceRecordsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $supervisorId = Filament::auth()->user()?->employee?->id;

        return parent::getEloquentQuery()
            ->where('attendance_type', 'duty')
            ->whereHas('employee', fn (Builder $query): Builder => $query->where('superior_id', $supervisorId));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDutyAttendanceRecords::route('/'),
        ];
    }
}
