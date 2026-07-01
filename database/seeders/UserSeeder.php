<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Andi Pratama',
                'email' => 'andi.pratama@example.com',
                'role' => 'supervisor',
            ],
            [
                'name' => 'Siti Rahma',
                'email' => 'siti.rahma@example.com',
                'role' => 'supervisor',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@example.com',
                'role' => 'employee',
            ],
            [
                'name' => 'Rina Lestari',
                'email' => 'rina.lestari@example.com',
                'role' => 'employee',
            ],
            [
                'name' => 'Dedi Kurniawan',
                'email' => 'dedi.kurniawan@example.com',
                'role' => 'employee',
            ],
            [
                'name' => 'Maya Putri',
                'email' => 'maya.putri@example.com',
                'role' => 'employee',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password',
                    'role' => $user['role'],
                ],
            );
        }
    }
}
