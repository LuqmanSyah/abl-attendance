<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Position;
use App\Models\User;
use App\Support\FaceRecognitionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

        if (! $this->positionRequiresSuperior($data['position_id'] ?? null)) {
            $data['superior_id'] = null;
        }

        $data = $this->processFacePhoto($data);

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

        return (bool) $position?->can_be_superior
            ? 'supervisor'
            : 'employee';
    }

    protected function positionRequiresSuperior(int|string|null $positionId): bool
    {
        if (blank($positionId)) {
            return false;
        }

        return (bool) Position::query()
            ->whereKey($positionId)
            ->value('requires_superior');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function processFacePhoto(array $data): array
    {
        if (blank($data['face_photo_path'] ?? null)) {
            return $data;
        }

        $data['face_embedding'] = app(FaceRecognitionService::class)
            ->createEmbeddingFromFile(Storage::disk('local')->path($data['face_photo_path']));
        $data['face_registered_at'] = now();

        return $data;
    }
}
