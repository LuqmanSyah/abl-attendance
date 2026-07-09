<?php

namespace App\Filament\Admin\Pages;

use App\Support\AttendanceSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AttendanceSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan Absensi';

    protected static ?string $title = 'Pengaturan Absensi';

    protected static ?string $slug = 'pengaturan-absensi';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.admin.pages.attendance-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(AttendanceSettings $settings): void
    {
        $latitude = $settings->officeLatitude();
        $longitude = $settings->officeLongitude();

        $this->form->fill([
            'latitude' => is_numeric($latitude) ? (float) $latitude : null,
            'longitude' => is_numeric($longitude) ? (float) $longitude : null,
            'radius_meters' => $settings->officeRadiusMeters(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                ViewField::make('map_picker')
                    ->label('Pilih Titik Kantor')
                    ->view('filament.forms.components.map-picker')
                    ->dehydrated(false)
                    ->columnSpanFull(),
                TextInput::make('latitude')
                    ->label('Latitude Kantor')
                    ->numeric()
                    ->minValue(-90)
                    ->maxValue(90)
                    ->step('0.0000001')
                    ->required(),
                TextInput::make('longitude')
                    ->label('Longitude Kantor')
                    ->numeric()
                    ->minValue(-180)
                    ->maxValue(180)
                    ->step('0.0000001')
                    ->required(),
                TextInput::make('radius_meters')
                    ->label('Radius Kantor (meter)')
                    ->numeric()
                    ->default(100)
                    ->minValue(1)
                    ->maxValue(10000)
                    ->required(),
            ]);
    }

    public function save(AttendanceSettings $settings): void
    {
        $data = $this->form->getState();

        $settings->saveOfficeLocation(
            (float) $data['latitude'],
            (float) $data['longitude'],
            (int) $data['radius_meters'],
        );

        Notification::make()
            ->title('Pengaturan absensi tersimpan')
            ->success()
            ->send();
    }
}
