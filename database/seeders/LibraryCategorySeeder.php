<?php

namespace Database\Seeders;

use App\Models\LearningCategory;
use Illuminate\Database\Seeder;

class LibraryCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Administrasi Pemerintahan', 'slug' => 'administrasi-pemerintahan', 'description' => 'Tata kelola, prosedur, dan administrasi umum pemerintahan.'],
            ['name' => 'Kompetensi Digital', 'slug' => 'kompetensi-digital', 'description' => 'Literasi digital, transformasi teknologi, dan pemanfaatan TIK.'],
            ['name' => 'Manajemen', 'slug' => 'manajemen', 'description' => 'Perencanaan, pengorganisasian, dan kepemimpinan.'],
            ['name' => 'Pelayanan Publik', 'slug' => 'pelayanan-publik', 'description' => 'Standar dan praktik pelayanan kepada masyarakat.'],
            ['name' => 'Etika dan Profesionalisme', 'slug' => 'etika-profesionalisme', 'description' => 'Integritas, etika jabatan, dan perilaku ASN.'],
            ['name' => 'Keamanan Informasi', 'slug' => 'keamanan-informasi', 'description' => 'Keamanan siber, perlindungan data, dan kesadaran informasi.'],
            ['name' => 'Pengembangan Kompetensi', 'slug' => 'pengembangan-kompetensi', 'description' => 'Pengembangan diri, pembelajaran berkelanjutan, dan peningkatan kapasitas.'],
        ];

        foreach ($categories as $data) {
            LearningCategory::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
