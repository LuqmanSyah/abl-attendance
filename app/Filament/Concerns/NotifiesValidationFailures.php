<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

trait NotifiesValidationFailures
{
    protected function notifyValidationFailure(ValidationException $exception): never
    {
        Notification::make()
            ->title(collect($exception->errors())->flatten()->first() ?: 'Data gagal disimpan')
            ->danger()
            ->send();

        throw $exception;
    }
}
