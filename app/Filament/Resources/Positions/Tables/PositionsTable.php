<?php

namespace App\Filament\Resources\Positions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PositionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Jabatan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employees_count')
                    ->label('Jumlah Pegawai')
                    ->counts('employees')
                    ->sortable(),
                IconColumn::make('requires_superior')
                    ->label('Butuh Atasan')
                    ->boolean(),
                IconColumn::make('can_be_superior')
                    ->label('Bisa Menjadi Atasan')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus data terpilih')
                        ->modalHeading('Hapus Jabatan terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus jabatan yang dipilih?')
                        ->modalSubmitActionLabel('Hapus')
                        ->modalCancelActionLabel('Batal')
                        ->successNotificationTitle('Jabatan berhasil dihapus'),
                ])
                    ->label('Aksi massal'),
            ]);
    }
}
