<?php

namespace Database\Seeders;

use App\Enums\LearningMaterialScope;
use App\Enums\LearningMaterialType;
use App\Enums\LearningPublicationStatus;
use App\Enums\LearningValidityStatus;
use App\Enums\LearningVerificationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialSource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LibraryInitialContentSeeder extends Seeder
{
    public function run(): void
    {
        $level = LearningLevel::where('key', 'dasar')->firstOrFail();
        $category = LearningCategory::where('slug', 'administrasi-pemerintahan')->firstOrFail();

        $uploaderId = User::where('email', 'admin.system@siap.test')->value('id')
            ?? User::where('role', 'admin')->value('id');

        if (! $uploaderId) {
            $this->command->warn('LibraryInitialContentSeeder: uploader admin tidak ditemukan, skip.');
            return;
        }

        $slug = 'uu-no-20-tahun-2023-aparatur-sipil-negara';
        $title = 'Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara';
        $sourceUrl = 'https://peraturan.bpk.go.id/Details/269470/uu';

        $exists = LearningMaterial::where('slug', $slug)->exists()
            || LearningMaterial::where('title', $title)->exists();

        if (LearningMaterialSource::where('source_url', $sourceUrl)->whereHas('material', fn ($q) => $q->where('title', $title))->exists()) {
            $this->command->info('LibraryInitialContentSeeder: materi sudah ada (berdasar source_url+title), skip.');
            return;
        }

        if ($exists) {
            $this->command->warn('LibraryInitialContentSeeder: slug/title sudah ada, skip untuk cegah duplikat.');
            return;
        }

        DB::transaction(function () use ($level, $category, $uploaderId, $slug, $title, $sourceUrl) {
            $material = LearningMaterial::create([
                'title' => $title,
                'slug' => $slug,
                'description' => 'Rujukan resmi: UU ASN terbaru yang mengatur kebijakan, manajemen, dan pengembangan ASN. Akses via tautan resmi Database Peraturan BPK.',
                'material_type' => LearningMaterialType::LinkOnly->value,
                'scope' => LearningMaterialScope::Core->value,
                'learning_level_id' => $level->id,
                'learning_category_id' => $category->id,
                'publication_status' => LearningPublicationStatus::Draft->value,
                'verification_status' => LearningVerificationStatus::PendingReview->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'published_at' => null,
                'uploaded_by' => $uploaderId,
            ]);

            LearningMaterialSource::create([
                'learning_material_id' => $material->id,
                'source_name' => 'Undang-Undang (UU) Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara',
                'source_url' => $sourceUrl,
                'source_type' => 'peraturan',
                'publisher' => 'Database Peraturan BPK, Pemerintah Pusat',
                'document_identifier' => 'UU No. 20 Tahun 2023',
                'version' => null,
                'source_issued_at' => '2023-10-31',
                'license_type' => 'hak_cipta',
                'license_url' => null,
                'last_checked_at' => now(),
                'verification_notes' => 'Sumber terverifikasi via fetch: judul sesuai halaman BPK per 2026. Hak cipta default sampai bukti izin lain.',
                'rights_status' => 'unreviewed',
                'rights_reviewed_by' => null,
                'rights_reviewed_at' => null,
                'rights_review_notes' => null,
                'metadata' => ['initial_content' => true, 'source_domain' => 'peraturan.bpk.go.id'],
            ]);

            LearningMaterialSource::where('learning_material_id', $material->id)->first()
                ->recordRightsLog('unreviewed', null, 'Materi awal dibuat menunggu review hak kedua.');
        });

        $this->command->info('LibraryInitialContentSeeder: UU 20/2023 siap (draft, pending_review, unreviewed).');
    }
}
