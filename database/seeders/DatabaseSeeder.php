<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => 'admin',
            ],
        );

        foreach (['Operasional', 'Administrasi', 'Keamanan'] as $division) {
            Division::firstOrCreate(['name' => $division]);
        }

        foreach (['Staff', 'Supervisor', 'Manager'] as $position) {
            Position::firstOrCreate(['name' => $position]);
        }
    }
}
