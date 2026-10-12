<?php

namespace Database\Seeders;

use App\Models\LearningMaterial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LibraryNativeContentRefreshSeeder extends Seeder
{
    public function run(): void
    {
        $batch = new LibraryNativeBatchSeeder();
        $ref = new \ReflectionClass($batch);
        $map = [
            'literasi-digital-untuk-asn' => 'literasiDigital',
            'keamanan-informasi-dan-perlindungan-data' => 'keamananInformasi',
            'komunikasi-efektif-di-lingkungan-kerja' => 'komunikasiEfektif',
            'pelayanan-publik-yang-berkualitas' => 'pelayananPublik',
            'etika-profesional-di-lingkungan-kerja' => 'etikaProfesional',
        ];
        foreach ($map as $slug => $method) {
            $m = $ref->getMethod($method);
            $m->setAccessible(true);
            $content = $m->invoke($batch);
            $material = LearningMaterial::where('slug', $slug)->first();
            if (! $material) {
                $this->command->warn("Refresh: slug tidak ditemukan: $slug");
                continue;
            }
            if ($material->slug === 'uu-no-20-tahun-2023-aparatur-sipil-negara') {
                continue;
            }
            DB::transaction(function () use ($material, $content) {
                $material->update(['content' => $content]);
            });
            $this->command->info("Refresh: content diperbarui $slug (" . strlen($content) . " chars)");
        }
    }
}
