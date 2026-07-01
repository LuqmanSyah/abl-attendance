<?php

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AttendanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Pegawai')
                    ->relationship('employee', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                DatePicker::make('attendance_date')
                    ->label('Tanggal Absensi')
                    ->required()
                    ->native(false),
                DateTimePicker::make('check_in_at')
                    ->label('Jam Masuk')
                    ->seconds(false)
                    ->native(false),
                DateTimePicker::make('check_out_at')
                    ->label('Jam Pulang')
                    ->seconds(false)
                    ->native(false),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'present' => 'Hadir',
                        'late' => 'Terlambat',
                        'absent' => 'Tidak Hadir',
                        'leave' => 'Izin/Cuti',
                    ])
                    ->default('present')
                    ->required(),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }
}
