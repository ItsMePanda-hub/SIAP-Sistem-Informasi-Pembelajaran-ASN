<?php

namespace Tests\Feature;

use App\Enums\LearningMaterialScope;
use App\Enums\LearningPublicationStatus;
use App\Enums\LearningValidityStatus;
use App\Enums\LearningVerificationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\User;
use Database\Seeders\LibraryCategorySeeder;
use Database\Seeders\LibraryLevelSeeder;
use Database\Seeders\LibraryMaterialSeeder;
use Database\Seeders\LibraryTestAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LibraryPhase2DatasetTest extends TestCase
{
    use RefreshDatabase;

    private function seedLibrary(): void
    {
        $this->seed(LibraryLevelSeeder::class);
        $this->seed(LibraryCategorySeeder::class);
        $this->seed(LibraryTestAccountsSeeder::class);
        $this->seed(LibraryMaterialSeeder::class);
    }

    public function test_lima_level_tersedia_urutan_benar_slug_key_unik(): void
    {
        $this->seed(LibraryLevelSeeder::class);

        $levels = LearningLevel::orderBy('sort_order')->get();

        $this->assertSame(5, $levels->count());
        $this->assertSame(['pemula', 'dasar', 'menengah', 'lanjutan', 'mahir'], $levels->pluck('key')->all());
        $this->assertSame([1, 2, 3, 4, 5], $levels->pluck('sort_order')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(['pemula', 'dasar', 'menengah', 'lanjutan', 'mahir'], $levels->pluck('slug')->all());
        $this->assertSame(5, $levels->pluck('key')->unique()->count());
        $this->assertSame(5, $levels->pluck('slug')->unique()->count());
        $this->assertTrue($levels->every(fn ($l) => (bool) $l->is_active));
    }

    public function test_kategori_tersedia_slug_unik(): void
    {
        $this->seed(LibraryCategorySeeder::class);

        $count = LearningCategory::count();
        $this->assertGreaterThanOrEqual(7, $count);
        $this->assertSame($count, LearningCategory::distinct()->pluck('slug')->count());
        $this->assertTrue(LearningCategory::where('slug', 'administrasi-pemerintahan')->exists());
        $this->assertTrue(LearningCategory::where('slug', 'keamanan-informasi')->exists());
    }

    public function test_core_valid_memiliki_provenance(): void
    {
        $this->seedLibrary();

        $material = LearningMaterial::where('slug', 'uji-core-valid-dasar-administrasi')->firstOrFail();

        $this->assertTrue($material->isCore());
        $this->assertSame(LearningVerificationStatus::Verified, $material->verification_status);
        $this->assertSame(LearningValidityStatus::Active, $material->validity_status);
        $this->assertTrue($material->isPublished());
        $this->assertNotNull($material->source);
        $this->assertNotEmpty($material->source->source_name);
    }

    public function test_core_verified_active_dapat_publish(): void
    {
        $this->seedLibrary();

        $material = LearningMaterial::where('slug', 'uji-core-valid-dasar-administrasi')->firstOrFail();

        $this->assertTrue($material->canCoreBePublished());
        $this->assertTrue($material->isPublished());
        $this->assertNotNull($material->published_at);
    }

    public function test_core_non_verified_tidak_dapat_publish(): void
    {
        $this->seedLibrary();

        foreach (['uji-core-draft-pemula-digital', 'uji-core-pending-menengah-manajemen', 'uji-core-rejected-lanjutan-pelayanan', 'uji-core-needs-update-mahir-keamanan'] as $slug) {
            $material = LearningMaterial::where('slug', $slug)->firstOrFail();
            $this->assertFalse($material->isPublished(), "Gagal: {$slug} dianggap published");
            $this->assertFalse($material->canCoreBePublished(), "Gagal: {$slug} dianggap publishable");

            try {
                $material->publish();
                $this->fail("Seharusnya ValidationException untuk {$slug}");
            } catch (ValidationException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_core_non_active_tidak_dianggap_valid_published(): void
    {
        $this->seedLibrary();

        $rejected = LearningMaterial::where('slug', 'uji-core-rejected-lanjutan-pelayanan')->firstOrFail();
        $needsUpdate = LearningMaterial::where('slug', 'uji-core-needs-update-mahir-keamanan')->firstOrFail();

        $this->assertNotSame(LearningValidityStatus::Active, $rejected->validity_status);
        $this->assertNotSame(LearningValidityStatus::Active, $needsUpdate->validity_status);
        $this->assertFalse($rejected->isPublished());
        $this->assertFalse($needsUpdate->isPublished());

        $validPublishedCore = LearningMaterial::core()
            ->where('verification_status', LearningVerificationStatus::Verified->value)
            ->where('validity_status', LearningValidityStatus::Active->value)
            ->where('publication_status', LearningPublicationStatus::Published->value)
            ->get();

        $this->assertTrue($validPublishedCore->where('slug', 'uji-core-valid-dasar-administrasi')->isNotEmpty());
        $this->assertFalse($validPublishedCore->where('slug', $rejected->slug)->isNotEmpty());
        $this->assertFalse($validPublishedCore->where('slug', $needsUpdate->slug)->isNotEmpty());
    }

    public function test_custom_dapat_dibuat_tidak_dianggap_core(): void
    {
        $this->seedLibrary();

        $customs = LearningMaterial::custom()->get();
        $this->assertGreaterThanOrEqual(3, $customs->count());

        foreach ($customs as $custom) {
            $this->assertTrue($custom->isCustom());
            $this->assertFalse($custom->isCore());
            $this->assertSame(LearningMaterialScope::Custom, $custom->scope);
        }

        $this->assertSame(0, LearningMaterial::core()->whereIn('id', $customs->pluck('id'))->count());
    }

    public function test_komposisi_10_akun_uji_tepat(): void
    {
        $this->seed(LibraryTestAccountsSeeder::class);

        $base = User::where('email', 'like', '%@siap.test');

        $this->assertSame(1, (clone $base)->where('role', 'admin')->count());
        $this->assertSame(1, (clone $base)->where('role', 'admin_library')->count());
        $this->assertSame(1, (clone $base)->where('role', 'pemilik')->count());
        $this->assertSame(2, (clone $base)->where('role', 'atasan')->count());
        $this->assertSame(6, (clone $base)->where('role', 'pegawai')->count());
        $this->assertSame(11, (clone $base)->count());

        $this->assertTrue(User::where('email', 'admin.system@siap.test')->where('role', 'admin')->exists());
        $this->assertTrue(User::where('email', 'admin.library@siap.test')->where('role', 'admin_library')->exists());
        $this->assertTrue(User::where('email', 'pemilik@siap.test')->where('role', 'pemilik')->exists());
        $this->assertTrue(User::where('email', 'atasan1@siap.test')->where('role', 'atasan')->exists());
        $this->assertTrue(User::where('email', 'atasan2@siap.test')->where('role', 'atasan')->exists());
        for ($i = 1; $i <= 6; $i++) {
            $this->assertTrue(User::where('email', "staf{$i}@siap.test")->where('role', 'pegawai')->exists());
        }

        $this->assertFalse(User::where('email', 'pemilik.library@siap.test')->exists());
        $this->assertFalse(User::where('email', 'atasan1.library@siap.test')->exists());
        $this->assertFalse(User::where('email', 'atasan2.library@siap.test')->exists());
        for ($i = 1; $i <= 6; $i++) {
            $this->assertFalse(User::where('email', "staf{$i}.library@siap.test")->exists());
        }
    }

    public function test_seeder_idempotent_tidak_menggandakan_data(): void
    {
        $this->seedLibrary();

        $counts = [
            'levels' => LearningLevel::count(),
            'categories' => LearningCategory::count(),
            'materials' => LearningMaterial::count(),
            'accounts' => User::where('email', 'like', '%@siap.test')->count(),
        ];

        $this->seedLibrary();

        $this->assertSame($counts['levels'], LearningLevel::count());
        $this->assertSame($counts['categories'], LearningCategory::count());
        $this->assertSame($counts['materials'], LearningMaterial::count());
        $this->assertSame($counts['accounts'], User::where('email', 'like', '%@siap.test')->count());
        $this->assertSame(5, LearningLevel::count());
        $this->assertSame(11, User::where('email', 'like', '%@siap.test')->count());
    }
}
