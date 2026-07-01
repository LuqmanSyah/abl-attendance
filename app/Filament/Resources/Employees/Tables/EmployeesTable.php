<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Division;
use App\Models\Position;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->sortable(),
                TextColumn::make('position.name')
                    ->label('Jabatan')
                    ->sortable(),
                TextColumn::make('superior.name')
                    ->label('Atasan Langsung')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        default => $state,
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('division_id')
                    ->label('Divisi')
                    ->options(fn (): array => Division::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('position_id')
                    ->label('Jabatan')
                    ->options(fn (): array => Position::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus terpilih'),
                ]),
            ]);
    }
}
