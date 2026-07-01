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
            ],
            [
                'name' => 'Staff',
                'description' => 'Melaksanakan tugas operasional sesuai divisi.',
            ],
            [
                'name' => 'Supervisor',
                'description' => 'Mengawasi pegawai dan melakukan validasi absensi.',
            ],
            [
                'name' => 'Manager',
                'description' => 'Mengelola koordinasi lintas divisi dan laporan.',
            ],
            [
                'name' => 'Koordinator Lapangan',
                'description' => 'Mengatur aktivitas dan penugasan personel lapangan.',
            ],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(
                ['name' => $position['name']],
                ['description' => $position['description']],
            );
        }
    }
}
