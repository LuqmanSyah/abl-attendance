<?php

namespace App\Filament\Resources\AttendanceCorrections\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AttendanceCorrectionForm
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
                Select::make('attendance_record_id')
                    ->label('Data Absensi')
                    ->relationship('attendanceRecord', 'attendance_date')
                    ->searchable()
                    ->preload(),
                DatePicker::make('correction_date')
                    ->label('Tanggal Koreksi')
                    ->required()
                    ->native(false),
                Select::make('type')
                    ->label('Jenis Koreksi')
                    ->options([
                        'check_in' => 'Jam Masuk',
                        'check_out' => 'Jam Pulang',
                        'both' => 'Masuk dan Pulang',
                    ])
                    ->required(),
                DateTimePicker::make('requested_check_in_at')
                    ->label('Revisi Jam Masuk')
                    ->seconds(false)
                    ->native(false),
                DateTimePicker::make('requested_check_out_at')
                    ->label('Revisi Jam Pulang')
                    ->seconds(false)
                    ->native(false),
                Textarea::make('reason')
                    ->label('Alasan')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('pending')
                    ->required(),
                Select::make('reviewed_by')
                    ->label('Direview Oleh')
                    ->relationship('reviewer', 'name')
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('reviewed_at')
                    ->label('Waktu Review')
                    ->seconds(false)
                    ->native(false),
                Textarea::make('review_notes')
                    ->label('Catatan Review')
                    ->columnSpanFull(),
            ]);
    }
}
