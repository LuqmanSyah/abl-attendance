<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'division_id',
        'position_id',
        'superior_id',
        'employee_code',
        'name',
        'phone',
        'address',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEligibleSuperiors(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereHas('position', fn (Builder $query): Builder => $query->where('can_be_superior', true));
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function superior(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'superior_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'superior_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function dutyAssignments(): HasMany
    {
        return $this->hasMany(DutyAssignment::class);
    }

    public function supervisedDutyAssignments(): HasMany
    {
        return $this->hasMany(DutyAssignment::class, 'supervisor_id');
    }

    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
