<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => 'Admin',
                'description' => 'Mengelola data master dan konfigurasi sistem.',
                'requires_superior' => false,
                'can_be_superior' => false,
            ],
            [
                'name' => 'Staff',
                'description' => 'Melaksanakan tugas operasional sesuai divisi.',
                'requires_superior' => true,
                'can_be_superior' => false,
            ],
            [
                'name' => 'Supervisor',
                'description' => 'Mengawasi pegawai dan melakukan validasi absensi.',
                'requires_superior' => false,
                'can_be_superior' => true,
            ],
            [
                'name' => 'Manager',
                'description' => 'Mengelola koordinasi lintas divisi dan laporan.',
                'requires_superior' => false,
                'can_be_superior' => true,
            ],
            [
                'name' => 'Koordinator Lapangan',
                'description' => 'Mengatur aktivitas dan penugasan personel lapangan.',
                'requires_superior' => true,
                'can_be_superior' => true,
            ],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(
                ['name' => $position['name']],
                [
                    'description' => $position['description'],
                    'requires_superior' => $position['requires_superior'],
                    'can_be_superior' => $position['can_be_superior'],
                ],
            );
        }
    }
}
