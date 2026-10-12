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

class LibraryMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $level = fn (string $key) => LearningLevel::where('key', $key)->firstOrFail();
        $category = fn (string $slug) => LearningCategory::where('slug', $slug)->firstOrFail();
        $uploaderId = User::where('email', 'admin.system@siap.test')->value('id')
            ?? User::where('email', 'admin.library@siap.test')->value('id')
            ?? User::where('role', 'admin')->value('id');

        $reviewerId = User::where('email', 'admin.library@siap.test')->value('id')
            ?? User::where('email', 'pemilik@siap.test')->value('id')
            ?? User::where('role', 'admin_library')->value('id')
            ?? User::where('role', 'admin')->where('id', '!=', $uploaderId)->value('id')
            ?? $uploaderId;

        $materials = [
            [
                'slug' => 'uji-core-valid-dasar-administrasi',
                'title' => '[UJI] Tertib Administrasi Perkantoran',
                'description' => 'Dataset uji Core valid. Bukan dokumen resmi.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Core->value,
                'level' => 'dasar',
                'category' => 'administrasi-pemerintahan',
                'verification_status' => LearningVerificationStatus::Verified->value,
                'validity_status' => LearningValidityStatus::Active->value,
                'publication_status' => LearningPublicationStatus::Published->value,
                'source' => [
                    'source_name' => 'SIAP Library Test Dataset',
                    'source_url' => null,
                    'source_type' => 'dataset_uji',
                    'publisher' => 'SIAP (Dataset Uji Internal)',
                    'document_identifier' => 'SIAP-TEST-CORE-001',
                    'version' => 'v1.0-test',
                    'source_issued_at' => null,
                    'license_type' => 'cc-by-4.0',
                    'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
                    'last_checked_at' => now(),
                    'verification_notes' => 'Dataset uji Phase 2. Bukan dokumen resmi pemerintah.',
                    'rights_status' => 'approved',
                    'rights_reviewed_by' => $reviewerId,
                    'rights_reviewed_at' => now(),
                    'rights_review_notes' => 'Disetujui untuk dataset uji Core.',
                    'metadata' => ['dataset' => 'phase-2-test'],
                ],
            ],
            [
                'slug' => 'uji-core-draft-pemula-digital',
                'title' => '[UJI] Pengenalan Perangkat Kerja Digital',
                'description' => 'Dataset uji Core draft. Belum diverifikasi.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Core->value,
                'level' => 'pemula',
                'category' => 'kompetensi-digital',
                'verification_status' => LearningVerificationStatus::Draft->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
                'source' => null,
            ],
            [
                'slug' => 'uji-core-pending-menengah-manajemen',
                'title' => '[UJI] Dasar Perencanaan Kerja Tim',
                'description' => 'Dataset uji Core menunggu review.',
                'material_type' => LearningMaterialType::Pdf->value,
                'scope' => LearningMaterialScope::Core->value,
                'level' => 'menengah',
                'category' => 'manajemen',
                'verification_status' => LearningVerificationStatus::PendingReview->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
                'source' => null,
            ],
            [
                'slug' => 'uji-core-rejected-lanjutan-pelayanan',
                'title' => '[UJI] Materi Ditolak Verifikasi',
                'description' => 'Dataset uji Core yang ditolak kurasi.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Core->value,
                'level' => 'lanjutan',
                'category' => 'pelayanan-publik',
                'verification_status' => LearningVerificationStatus::Rejected->value,
                'validity_status' => LearningValidityStatus::Revoked->value,
                'publication_status' => LearningPublicationStatus::Unpublished->value,
                'source' => null,
            ],
            [
                'slug' => 'uji-core-needs-update-mahir-keamanan',
                'title' => '[UJI] Materi Perlu Pembaruan Keamanan',
                'description' => 'Dataset uji Core yang sumbernya perlu diperiksa ulang.',
                'material_type' => LearningMaterialType::Pdf->value,
                'scope' => LearningMaterialScope::Core->value,
                'level' => 'mahir',
                'category' => 'keamanan-informasi',
                'verification_status' => LearningVerificationStatus::NeedsUpdate->value,
                'validity_status' => LearningValidityStatus::Superseded->value,
                'publication_status' => LearningPublicationStatus::Unpublished->value,
                'source' => [
                    'source_name' => 'SIAP Library Test Dataset',
                    'source_url' => null,
                    'source_type' => 'dataset_uji',
                    'publisher' => 'SIAP (Dataset Uji Internal)',
                    'document_identifier' => 'SIAP-TEST-CORE-005',
                    'version' => 'v0.9-test',
                    'source_issued_at' => null,
                    'license_type' => 'internal',
                    'license_url' => null,
                    'last_checked_at' => now()->subMonths(6),
                    'verification_notes' => 'Sumber perlu diperiksa ulang sebelum dapat dipakai lagi.',
                    'rights_status' => 'unreviewed',
                    'rights_reviewed_by' => null,
                    'rights_reviewed_at' => null,
                    'rights_review_notes' => null,
                    'metadata' => ['dataset' => 'phase-2-test'],
                ],
            ],
            [
                'slug' => 'uji-custom-sop-internal',
                'title' => '[UJI] SOP Internal Pengajuan Surat Tugas',
                'description' => 'Dataset uji Custom: prosedur internal organisasi.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Custom->value,
                'level' => 'dasar',
                'category' => 'administrasi-pemerintahan',
                'verification_status' => LearningVerificationStatus::Draft->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Published->value,
                'source' => null,
            ],
            [
                'slug' => 'uji-custom-panduan-siap',
                'title' => '[UJI] Panduan Penggunaan Sistem SIAP',
                'description' => 'Dataset uji Custom: panduan aplikasi internal.',
                'material_type' => LearningMaterialType::Pdf->value,
                'scope' => LearningMaterialScope::Custom->value,
                'level' => 'pemula',
                'category' => 'kompetensi-digital',
                'verification_status' => LearningVerificationStatus::Draft->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Published->value,
                'source' => null,
            ],
            [
                'slug' => 'uji-custom-orientasi-pegawai',
                'title' => '[UJI] Materi Orientasi Pegawai Baru',
                'description' => 'Dataset uji Custom: orientasi internal, masih draft.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Custom->value,
                'level' => 'pemula',
                'category' => 'pengembangan-kompetensi',
                'verification_status' => LearningVerificationStatus::Draft->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
                'source' => null,
            ],
        ];

        foreach ($materials as $data) {
            // Two-phase write: material sebagai draft dulu agar rule Core publish
            // (provenance + verified + active + rights) dapat dievaluasi setelah source terisi.
            $isCorePublished = $data['scope'] === LearningMaterialScope::Core->value
                && $data['publication_status'] === LearningPublicationStatus::Published->value;

            $material = LearningMaterial::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'material_type' => $data['material_type'],
                    'scope' => $data['scope'],
                    'learning_level_id' => $level($data['level'])->id,
                    'learning_category_id' => $category($data['category'])->id,
                    'verification_status' => $data['verification_status'],
                    'validity_status' => $data['validity_status'],
                    'publication_status' => $isCorePublished
                        ? LearningPublicationStatus::Draft->value
                        : $data['publication_status'],
                    'published_at' => (! $isCorePublished && $data['publication_status'] === LearningPublicationStatus::Published->value) ? now() : null,
                    'uploaded_by' => $uploaderId,
                ]
            );

            if ($data['source'] !== null) {
                LearningMaterialSource::updateOrCreate(
                    ['learning_material_id' => $material->id],
                    $data['source']
                );
            }

            if ($isCorePublished) {
                $material->publish();
            }
        }
    }
}
