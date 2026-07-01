<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_code')
                    ->label('Kode Pegawai')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Nama Pegawai')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: User::class,
                        column: 'email',
                        ignorable: fn (?Employee $record): ?User => $record?->user,
                        ignoreRecord: false,
                    ),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation, ?Employee $record): bool => $operation === 'create' || blank($record?->user_id))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('No. Telepon')
                    ->tel()
                    ->maxLength(255),
                Select::make('division_id')
                    ->label('Divisi')
                    ->relationship('division', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('position_id')
                    ->label('Jabatan')
                    ->relationship('position', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(static function (Set $set, mixed $state): void {
                        if (! self::positionRequiresSuperior($state)) {
                            $set('superior_id', null);
                        }
                    })
                    ->searchable()
                    ->preload(),
                Select::make('superior_id')
                    ->label('Atasan Langsung')
                    ->relationship(
                        'superior',
                        'name',
                        static fn (Builder $query): Builder => $query->eligibleSuperiors(),
                        ignoreRecord: true,
                    )
                    ->required(static fn (Get $get): bool => self::positionRequiresSuperior($get('position_id')))
                    ->visible(static fn (Get $get): bool => self::positionRequiresSuperior($get('position_id')))
                    ->scopedExists(
                        Employee::class,
                        'id',
                        static fn (Builder $query): Builder => $query->eligibleSuperiors(),
                    )
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ])
                    ->default('active')
                    ->required(),
                Textarea::make('address')
                    ->label('Alamat')
                    ->columnSpanFull(),
            ]);
    }

    protected static function positionRequiresSuperior(mixed $positionId): bool
    {
        if (blank($positionId)) {
            return false;
        }

        return (bool) Position::query()
            ->whereKey($positionId)
            ->value('requires_superior');
    }
}
