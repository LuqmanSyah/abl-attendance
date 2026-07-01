<?php

namespace App\Filament\Resources\Positions\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Jabatan')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                Checkbox::make('requires_superior')
                    ->label('Butuh Atasan'),
                Checkbox::make('can_be_superior')
                    ->label('Bisa Menjadi Atasan'),
            ]);
    }
}
