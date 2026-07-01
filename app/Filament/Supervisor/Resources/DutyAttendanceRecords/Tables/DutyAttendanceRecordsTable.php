<?php

namespace App\Filament\Supervisor\Resources\DutyAttendanceRecords\Tables;

use App\Models\AttendanceRecord;
use App\Support\DutyAttendanceManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DutyAttendanceRecordsTable
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
                TextColumn::make('dutyAssignment.title')
                    ->label('Penugasan')
                    ->searchable(),
                TextColumn::make('check_in_at')
                    ->label('Masuk')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('check_in_distance_meters')
                    ->label('Jarak Masuk')
                    ->suffix(' m')
                    ->sortable(),
                TextColumn::make('check_in_location_status')
                    ->label('Lokasi Masuk')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'inside_radius' => 'Dalam Radius',
                        'outside_radius' => 'Luar Radius',
                        default => '-',
                    }),
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
                SelectFilter::make('verification_status')
                    ->label('Verifikasi')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('verification_notes')
                            ->label('Catatan Verifikasi'),
                    ])
                    ->visible(fn (AttendanceRecord $record): bool => $record->verification_status === 'pending')
                    ->action(function (AttendanceRecord $record, array $data): void {
                        app(DutyAttendanceManager::class)->verify(
                            $record,
                            auth()->user()->employee,
                            'approved',
                            $data['verification_notes'] ?? null,
                        );

                        Notification::make()
                            ->title('Absensi dinas disetujui')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('verification_notes')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->visible(fn (AttendanceRecord $record): bool => $record->verification_status === 'pending')
                    ->action(function (AttendanceRecord $record, array $data): void {
                        app(DutyAttendanceManager::class)->verify(
                            $record,
                            auth()->user()->employee,
                            'rejected',
                            $data['verification_notes'] ?? null,
                        );

                        Notification::make()
                            ->title('Absensi dinas ditolak')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
