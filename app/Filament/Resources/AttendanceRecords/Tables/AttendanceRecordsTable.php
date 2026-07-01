<?php

namespace App\Filament\Resources\AttendanceRecords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttendanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attendance_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attendance_type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'office' => 'Kantor',
                        'duty' => 'Dinas',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('dutyAssignment.title')
                    ->label('Penugasan')
                    ->toggleable(),
                TextColumn::make('check_in_at')
                    ->label('Masuk')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('check_in_distance_meters')
                    ->label('Jarak Masuk')
                    ->suffix(' m')
                    ->toggleable(),
                TextColumn::make('check_out_at')
                    ->label('Pulang')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'Hadir',
                        'late' => 'Terlambat',
                        'absent' => 'Tidak Hadir',
                        'leave' => 'Izin/Cuti',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('verification_status')
                    ->label('Verifikasi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('employee')
                    ->label('Pegawai')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('attendance_type')
                    ->label('Jenis Absensi')
                    ->options([
                        'office' => 'Kantor',
                        'duty' => 'Dinas',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'present' => 'Hadir',
                        'late' => 'Terlambat',
                        'absent' => 'Tidak Hadir',
                        'leave' => 'Izin/Cuti',
                    ]),
                SelectFilter::make('verification_status')
                    ->label('Verifikasi')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
