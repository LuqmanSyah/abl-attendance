<?php

namespace App\Support;

use App\Models\User;

class RoleDashboard
{
    public static function pathFor(User $user): string
    {
        return match ($user->role) {
            'admin' => '/admin',
            'supervisor' => '/atasan',
            'employee' => '/pegawai',
            default => '/login',
        };
    }
}
