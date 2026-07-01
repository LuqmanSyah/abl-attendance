<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Position;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    /**
     * @var array{email?: string, password?: string}
     */
    protected array $accountData = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['email'] = $this->getRecord()->user?->email;
        $data['password'] = null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->accountData = [
            'email' => $data['email'],
        ];

        if (array_key_exists('password', $data)) {
            $this->accountData['password'] = $data['password'];
        }

        unset($data['email'], $data['password']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $userData = [
            'name' => $data['name'],
            'email' => $this->accountData['email'],
            'role' => $this->resolveRole($data['position_id']),
        ];

        if (filled($this->accountData['password'] ?? null)) {
            $userData['password'] = $this->accountData['password'];
        }

        $user = $record->user;

        if ($user) {
            $user->update($userData);
        } else {
            $user = User::create($userData);
        }

        $data['user_id'] = $user->id;

        return parent::handleRecordUpdate($record, $data);
    }

    protected function resolveRole(int|string|null $positionId): string
    {
        $position = Position::find($positionId);

        return strcasecmp($position?->name ?? '', 'Supervisor') === 0
            ? 'supervisor'
            : 'employee';
    }
}
