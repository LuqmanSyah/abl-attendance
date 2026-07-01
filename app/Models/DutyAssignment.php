<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DutyAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'supervisor_id',
        'title',
        'location_name',
        'description',
        'latitude',
        'longitude',
        'radius_meters',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meters' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function scopeActiveAt(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where('status', 'active')
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>=', $at);
    }

    public function scopeForSupervisor(Builder $query, Employee|int $supervisor): Builder
    {
        $supervisorId = $supervisor instanceof Employee ? $supervisor->id : $supervisor;

        return $query->where('supervisor_id', $supervisorId);
    }
}
