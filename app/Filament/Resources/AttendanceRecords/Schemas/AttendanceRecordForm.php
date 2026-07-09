<?php

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                Select::make('attendance_type')
                    ->label('Jenis Absensi')
                    ->options([
                        'office' => 'Kantor',
                        'duty' => 'Dinas',
                    ])
                    ->default('office')
                    ->required(),
                Select::make('duty_assignment_id')
                    ->label('Penugasan Dinas')
                    ->relationship('dutyAssignment', 'title')
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('check_in_at')
                    ->label('Jam Masuk')
                    ->seconds(false)
                    ->native(false),
                TextInput::make('check_in_latitude')
                    ->label('Latitude Masuk')
                    ->numeric()
                    ->minValue(-90)
                    ->maxValue(90)
                    ->step('0.0000001'),
                TextInput::make('check_in_longitude')
                    ->label('Longitude Masuk')
                    ->numeric()
                    ->minValue(-180)
                    ->maxValue(180)
                    ->step('0.0000001'),
                TextInput::make('check_in_accuracy')
                    ->label('Akurasi Masuk (meter)')
                    ->numeric(),
                TextInput::make('check_in_distance_meters')
                    ->label('Jarak Masuk (meter)')
                    ->numeric(),
                Select::make('check_in_location_status')
                    ->label('Status Lokasi Masuk')
                    ->options([
                        'inside_radius' => 'Dalam Radius',
                        'outside_radius' => 'Luar Radius',
                    ]),
                DateTimePicker::make('check_out_at')
                    ->label('Jam Pulang')
                    ->seconds(false)
                    ->native(false),
                TextInput::make('check_out_latitude')
                    ->label('Latitude Pulang')
                    ->numeric()
                    ->minValue(-90)
                    ->maxValue(90)
                    ->step('0.0000001'),
                TextInput::make('check_out_longitude')
                    ->label('Longitude Pulang')
                    ->numeric()
                    ->minValue(-180)
                    ->maxValue(180)
                    ->step('0.0000001'),
                TextInput::make('check_out_accuracy')
                    ->label('Akurasi Pulang (meter)')
                    ->numeric(),
                TextInput::make('check_out_distance_meters')
                    ->label('Jarak Pulang (meter)')
                    ->numeric(),
                Select::make('check_out_location_status')
                    ->label('Status Lokasi Pulang')
                    ->options([
                        'inside_radius' => 'Dalam Radius',
                        'outside_radius' => 'Luar Radius',
                    ]),
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
                Select::make('verification_status')
                    ->label('Status Verifikasi')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('approved')
                    ->required(),
                Select::make('verified_by')
                    ->label('Diverifikasi Oleh')
                    ->relationship('verifier', 'name')
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('verified_at')
                    ->label('Waktu Verifikasi')
                    ->seconds(false)
                    ->native(false),
                Textarea::make('verification_notes')
                    ->label('Catatan Verifikasi')
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }
}
