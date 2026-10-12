<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LearningLibraryFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function levelAndCategory(): array
    {
        $level = LearningLevel::factory()->create(['key' => 'pemula', 'slug' => 'pemula-test', 'name' => 'Pemula Test']);
        $category = LearningCategory::factory()->create(['slug' => 'kategori-test', 'name' => 'Kategori Test']);

        return [$level, $category];
    }

    public function test_level_dapat_dibuat_dan_material_dapat_berelasi(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
        ]);

        $this->assertTrue($material->level->is($level));
        $this->assertTrue($level->materials()->where('id', $material->id)->exists());
    }

    public function test_category_dapat_dibuat_dan_material_dapat_berelasi(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
        ]);

        $this->assertTrue($material->category->is($category));
        $this->assertTrue($category->materials()->where('id', $material->id)->exists());
    }

    public function test_material_dapat_membedakan_core_dan_custom(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $core = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
        ]);
        $custom = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
        ]);

        $this->assertTrue($core->isCore());
        $this->assertFalse($core->isCustom());
        $this->assertTrue($custom->isCustom());
        $this->assertFalse($custom->isCore());
        $this->assertSame(LearningMaterialScope::Core, $core->scope);
        $this->assertSame(LearningMaterialScope::Custom, $custom->scope);
    }

    public function test_core_material_memiliki_provenance(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
        ]);

        $source = LearningMaterialSource::factory()->create([
            'learning_material_id' => $material->id,
            'source_name' => 'LAN RI',
            'source_url' => 'https://lan.go.id/pedoman',
            'publisher' => 'Lembaga Administrasi Negara',
        ]);

        $material->refresh();
        $this->assertTrue($material->source->is($source));
        $this->assertSame('LAN RI', $material->source->source_name);
        $this->assertSame('https://lan.go.id/pedoman', $material->source->source_url);
        $this->assertSame('Lembaga Administrasi Negara', $material->source->publisher);
    }

    public function test_core_draft_tidak_dapat_published(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Draft->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $this->expectException(ValidationException::class);

        $material->publication_status = LearningPublicationStatus::Published;
        $material->save();
    }

    public function test_core_pending_review_tidak_dapat_published(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::PendingReview->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $this->expectException(ValidationException::class);

        $material->publication_status = LearningPublicationStatus::Published;
        $material->save();
    }

    public function test_core_rejected_tidak_dapat_published(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Rejected->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $this->expectException(ValidationException::class);

        $material->publication_status = LearningPublicationStatus::Published;
        $material->save();
    }

    public function test_core_needs_update_tidak_dapat_published_dan_tidak_dianggap_current(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::NeedsUpdate->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $this->expectException(ValidationException::class);

        $material->publication_status = LearningPublicationStatus::Published;
        $material->save();
    }

    public function test_core_verified_dapat_dipublish(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'validity_status' => LearningValidityStatus::Active->value,
        ]);

        LearningMaterialSource::factory()->create([
            'learning_material_id' => $material->id,
            'source_name' => 'Sumber Uji',
            'publisher' => 'Penerbit Uji',
        ]);

        $material->publication_status = LearningPublicationStatus::Published;
        $material->save();
        $material->refresh();

        $this->assertTrue($material->isPublished());
        $this->assertNotNull($material->published_at);
    }

    public function test_verification_status_tidak_otomatis_berubah_hanya_karena_publish(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->create([
            'scope' => LearningMaterialScope::Custom->value,
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Draft->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $material->publication_status = LearningPublicationStatus::Published;
        $material->save();
        $material->refresh();

        $this->assertSame(LearningVerificationStatus::Draft, $material->verification_status);
        $this->assertTrue($material->isPublished());
    }

    public function test_verification_dan_validity_tetap_terpisah(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Revoked->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $material->refresh();

        $this->assertSame(LearningVerificationStatus::Verified, $material->verification_status);
        $this->assertSame(LearningValidityStatus::Revoked, $material->validity_status);

        $material->validity_status = LearningValidityStatus::Active;
        $material->save();
        $material->refresh();

        $this->assertSame(LearningVerificationStatus::Verified, $material->verification_status);
        $this->assertSame(LearningValidityStatus::Active, $material->validity_status);
    }

    public function test_custom_material_dapat_dibuat_tanpa_provenance_core(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $admin = User::factory()->create(['role' => 'admin']);

        $material = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'uploaded_by' => $admin->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'verification_status' => LearningVerificationStatus::Draft->value,
        ]);

        $this->assertTrue($material->isCustom());
        $this->assertNull($material->source);
        $this->assertTrue($material->uploader->is($admin));
    }

    public function test_custom_material_tidak_mendapat_status_core(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
        ]);

        $this->assertSame(LearningMaterialScope::Custom, $material->scope);
        $this->assertFalse($material->isCore());
        $this->assertSame(0, LearningMaterial::core()->where('id', $material->id)->count());
    }

    public function test_published_at_hanya_terisi_ketika_published(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $draft = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $this->assertNull($draft->published_at);

        $draft->publication_status = LearningPublicationStatus::Published;
        $draft->save();
        $draft->refresh();

        $this->assertNotNull($draft->published_at);

        $draft->publication_status = LearningPublicationStatus::Unpublished;
        $draft->save();
        $draft->refresh();

        $this->assertNull($draft->published_at);
    }

    public function test_material_unpublished_tidak_dianggap_published(): void
    {
        [$level, $category] = $this->levelAndCategory();

        LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'title' => 'Materi Draft',
        ]);

        $published = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Published->value,
            'published_at' => now(),
            'title' => 'Materi Published',
        ]);

        $this->assertSame(1, LearningMaterial::published()->count());
        $this->assertTrue(LearningMaterial::published()->where('id', $published->id)->exists());
        $this->assertFalse(LearningMaterial::published()->where('title', 'Materi Draft')->exists());
    }

    public function test_slug_unik(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $a = LearningMaterial::factory()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'slug' => 'materi-asn',
        ]);

        $b = LearningMaterial::factory()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'title' => 'Materi ASN',
            'slug' => null,
        ]);

        $this->assertNotSame($a->slug, $b->slug);
        $this->assertStringStartsWith('materi-asn', $b->slug);

        $c = LearningMaterial::factory()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'slug' => 'materi-asn',
        ]);
        $this->assertNotSame('materi-asn', $c->slug);
        $this->assertStringStartsWith('materi-asn', $c->slug);
        $this->assertSame(3, LearningMaterial::where('slug', 'like', 'materi-asn%')->count());
    }

    public function test_relasi_eloquent_bekerja_dengan_benar(): void
    {
        $level = LearningLevel::factory()->create();
        $category = LearningCategory::factory()->create();
        $uploader = User::factory()->create(['role' => 'admin']);

        $material = LearningMaterial::factory()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'uploaded_by' => $uploader->id,
        ]);

        $source = LearningMaterialSource::factory()->create([
            'learning_material_id' => $material->id,
        ]);

        $material->refresh();

        $this->assertTrue($material->level->is($level));
        $this->assertTrue($material->category->is($category));
        $this->assertTrue($material->uploader->is($uploader));
        $this->assertTrue($material->source->is($source));
        $this->assertTrue($source->material->is($material));
        $this->assertTrue($level->materials->contains($material));
        $this->assertTrue($category->materials->contains($material));
    }

    public function test_core_publish_validation_juga_melalui_method_publish(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Draft->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $this->expectException(ValidationException::class);
        $material->publish();
    }

    public function test_core_verified_publish_melalui_method_publish_mengisi_published_at(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'validity_status' => LearningValidityStatus::Active->value,
        ]);

        LearningMaterialSource::factory()->create([
            'learning_material_id' => $material->id,
            'source_name' => 'Sumber Uji Publish',
            'publisher' => 'Penerbit Uji Publish',
        ]);

        $this->assertNull($material->published_at);
        $material->publish();
        $material->refresh();
        $this->assertTrue($material->isPublished());
        $this->assertNotNull($material->published_at);
    }

    public function test_provenance_tidak_terhapus_ketika_status_material_berubah(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $material = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'validity_status' => LearningValidityStatus::Active->value,
        ]);

        $source = LearningMaterialSource::factory()->create([
            'learning_material_id' => $material->id,
        ]);

        $material->publication_status = LearningPublicationStatus::Published;
        $material->save();

        $material->verification_status = LearningVerificationStatus::NeedsUpdate;
        $material->publication_status = LearningPublicationStatus::Unpublished;
        $material->save();

        $this->assertDatabaseHas('learning_material_sources', ['id' => $source->id, 'learning_material_id' => $material->id]);
        $material->refresh();
        $this->assertNotNull($material->source);
    }

    public function test_core_isolated_via_scope_tidak_tertular_custom(): void
    {
        [$level, $category] = $this->levelAndCategory();

        $core = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
        ]);
        $custom = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
        ]);

        $this->assertTrue(LearningMaterial::core()->where('id', $core->id)->exists());
        $this->assertFalse(LearningMaterial::core()->where('id', $custom->id)->exists());
        $this->assertTrue(LearningMaterial::custom()->where('id', $custom->id)->exists());
        $this->assertFalse(LearningMaterial::custom()->where('id', $core->id)->exists());
    }
}
