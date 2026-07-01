<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            [
                'name' => 'Operasional',
                'description' => 'Mengelola aktivitas operasional harian di lapangan.',
            ],
            [
                'name' => 'Administrasi',
                'description' => 'Mengelola dokumentasi, pencatatan, dan kebutuhan administrasi.',
            ],
            [
                'name' => 'Keamanan',
                'description' => 'Menangani pengamanan area kerja dan personel.',
            ],
            [
                'name' => 'Logistik',
                'description' => 'Mengelola distribusi perlengkapan dan kebutuhan lapangan.',
            ],
            [
                'name' => 'Teknisi Lapangan',
                'description' => 'Menangani pemeriksaan dan perbaikan teknis di lokasi kerja.',
            ],
        ];

        foreach ($divisions as $division) {
            Division::updateOrCreate(
                ['name' => $division['name']],
                ['description' => $division['description']],
            );
        }
    }
}
