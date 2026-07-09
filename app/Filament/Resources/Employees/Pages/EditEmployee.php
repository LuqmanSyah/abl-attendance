<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Position;
use App\Models\User;
use App\Support\FaceRecognitionService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

        if (! $this->positionRequiresSuperior($data['position_id'] ?? null)) {
            $data['superior_id'] = null;
        }

        $data = $this->processFacePhoto($data);

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
        $facePhotoPath = $data['face_photo_path'] ?? null;

        if (blank($facePhotoPath)) {
            $data['face_embedding'] = null;
            $data['face_registered_at'] = null;

            return $data;
        }

        if ($facePhotoPath === $this->getRecord()->face_photo_path) {
            return $data;
        }

        $data['face_embedding'] = app(FaceRecognitionService::class)
            ->createEmbeddingFromFile(Storage::disk('local')->path($facePhotoPath));
        $data['face_registered_at'] = now();

        return $data;
    }
}
