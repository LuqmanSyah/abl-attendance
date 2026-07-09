<?php

namespace App\Filament\Supervisor\Resources\DutyAssignments\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DutyAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('supervisor_id')
                    ->default(fn (): ?int => Filament::auth()->user()?->employee?->id),
                Select::make('employee_id')
                    ->label('Pegawai')
                    ->relationship(
                        'employee',
                        'name',
                        static function (Builder $query): Builder {
                            $supervisorId = Filament::auth()->user()?->employee?->id;

                            return $query
                                ->where('status', 'active')
                                ->where('superior_id', $supervisorId);
                        },
                    )
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->label('Judul Dinas')
                    ->required()
                    ->maxLength(255),
                TextInput::make('location_name')
                    ->label('Nama Lokasi')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Keterangan')
                    ->columnSpanFull(),
                ViewField::make('map_picker')
                    ->label('Pilih Titik Lokasi')
                    ->view('filament.forms.components.map-picker')
                    ->dehydrated(false)
                    ->columnSpanFull(),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->numeric()
                    ->minValue(-90)
                    ->maxValue(90)
                    ->step('0.0000001')
                    ->required(),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->numeric()
                    ->minValue(-180)
                    ->maxValue(180)
                    ->step('0.0000001')
                    ->required(),
                TextInput::make('radius_meters')
                    ->label('Radius Toleransi (meter)')
                    ->numeric()
                    ->default(100)
                    ->minValue(1)
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->label('Mulai Dinas')
                    ->seconds(false)
                    ->native(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label('Selesai Dinas')
                    ->seconds(false)
                    ->native(false)
                    ->rules(['after:starts_at'])
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'cancelled' => 'Dibatalkan',
                        'completed' => 'Selesai',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }
}
