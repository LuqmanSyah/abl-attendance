<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'office_latitude',
        'office_longitude',
        'office_radius_meters',
    ];

    protected function casts(): array
    {
        return [
            'office_latitude' => 'float',
            'office_longitude' => 'float',
            'office_radius_meters' => 'integer',
        ];
    }
}
