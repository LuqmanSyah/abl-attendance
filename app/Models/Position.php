<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = [
        'name',
        'description',
        'requires_superior',
        'can_be_superior',
    ];

    protected function casts(): array
    {
        return [
            'requires_superior' => 'boolean',
            'can_be_superior' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
