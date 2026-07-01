<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Position;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    /**
     * @var array{email: string, password: string}
     */
    protected array $accountData = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->accountData = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        unset($data['email'], $data['password']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $this->accountData['email'],
            'password' => $this->accountData['password'],
            'role' => $this->resolveRole($data['position_id']),
        ]);

        $data['user_id'] = $user->id;

        return parent::handleRecordCreation($data);
    }

    protected function resolveRole(int|string|null $positionId): string
    {
        $position = Position::find($positionId);

        return strcasecmp($position?->name ?? '', 'Supervisor') === 0
            ? 'supervisor'
            : 'employee';
    }
}
