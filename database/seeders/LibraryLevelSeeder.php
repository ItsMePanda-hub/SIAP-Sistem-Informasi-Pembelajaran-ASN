<?php

namespace Database\Seeders;

use App\Models\LearningLevel;
use Illuminate\Database\Seeder;

class LibraryLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['key' => 'pemula', 'name' => 'Pemula', 'slug' => 'pemula', 'sort_order' => 1, 'description' => 'Tahap pengenalan dasar ASN.'],
            ['key' => 'dasar', 'name' => 'Dasar', 'slug' => 'dasar', 'sort_order' => 2, 'description' => 'Pemahaman fundamental tugas dan fungsi ASN.'],
            ['key' => 'menengah', 'name' => 'Menengah', 'slug' => 'menengah', 'sort_order' => 3, 'description' => 'Pengembangan kompetensi terapan.'],
            ['key' => 'lanjutan', 'name' => 'Lanjutan', 'slug' => 'lanjutan', 'sort_order' => 4, 'description' => 'Penguatan keahlian lanjutan.'],
            ['key' => 'mahir', 'name' => 'Mahir', 'slug' => 'mahir', 'sort_order' => 5, 'description' => 'Level tertinggi, penguasaan mendalam.'],
        ];

        foreach ($levels as $data) {
            LearningLevel::updateOrCreate(
                ['key' => $data['key']],
                [
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'sort_order' => $data['sort_order'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
