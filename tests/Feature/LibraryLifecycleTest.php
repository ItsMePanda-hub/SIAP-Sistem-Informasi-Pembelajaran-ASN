<?php

namespace Tests\Feature;

use App\Enums\LearningPublicationStatus;
use App\Enums\LearningValidityStatus;
use App\Enums\LearningVerificationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function levelAndCategory(): array
    {
        $level = LearningLevel::factory()->create();
        $category = LearningCategory::factory()->create();
        return [$level, $category];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function coreDraft(array $over = []): LearningMaterial
    {
        [$level, $category] = $this->levelAndCategory();
        return LearningMaterial::factory()->core()->create(array_merge([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Draft->value,
            'validity_status' => LearningValidityStatus::Unknown->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ], $over));
    }

    // ---- Provenance ----
    public function test_core_tanpa_source_tidak_dapat_publish(): void
    {
        $admin = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $this->actingAs($admin)->post(route('library.publish', $m))->assertSessionHasErrors('publication_status');
        $this->actingAs($admin)->put(route('library.update', $m), [
            'title' => $m->title,
            'description' => $m->description,
            'scope' => 'core',
            'material_type' => $m->material_type->value,
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => 'published',
            'verification_status' => 'verified',
            'validity_status' => 'active',
            'content' => 'Konten uji lifecycle.',
        ])->assertSessionHasErrors('publication_status');
    }

    public function test_core_source_name_kosong_tidak_dapat_publish(): void
    {
        $admin = $this->user('admin');
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create([
            'learning_material_id' => $m->id,
            'source_name' => '',
            'publisher' => 'Penerbit Ada',
        ]);

        $this->actingAs($admin)->post(route('library.publish', $m))->assertSessionHasErrors('publication_status');
    }

    public function test_core_publisher_kosong_tidak_dapat_publish(): void
    {
        $admin = $this->user('pemilik');
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create([
            'learning_material_id' => $m->id,
            'source_name' => 'Sumber Ada',
            'publisher' => '',
        ]);

        $this->actingAs($admin)->post(route('library.publish', $m))->assertSessionHasErrors('publication_status');
    }

    public function test_core_provenance_lengkap_dapat_publish(): void
    {
        $admin = $this->user('admin');
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create([
            'learning_material_id' => $m->id,
            'source_name' => 'Sumber Lengkap',
            'publisher' => 'Penerbit Lengkap',
        ]);

        $this->actingAs($admin)->post(route('library.publish', $m))->assertRedirect(route('library.index'));
        $m->refresh();
        $this->assertTrue($m->isPublished());
        $this->assertNotNull($m->published_at);
        $this->assertTrue($m->isCurrent());
    }

    // ---- Verification workflow ----
    public function test_user_biasa_tidak_dapat_verify(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->create(['learning_level_id' => $level->id, 'learning_category_id' => $category->id, 'publication_status' => 'draft']);

        foreach (['atasan', 'pegawai'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->put(route('library.update', $m), [
                'title' => $m->title,
                'description' => $m->description,
                'scope' => 'custom',
                'material_type' => $m->material_type->value,
                'learning_level_id' => $level->id,
                'learning_category_id' => $category->id,
                'publication_status' => 'draft',
                'verification_status' => 'verified',
                'validity_status' => 'active',
                'content' => 'Konten uji.',
            ])->assertForbidden();
            $this->actingAs($u)->get(route('library.source.edit', $m))->assertForbidden();
            $this->actingAs($u)->put(route('library.source.update', $m), ['source_name' => 'X', 'publisher' => 'Y'])->assertForbidden();
            $this->actingAs($u)->post(route('library.publish', $m))->assertForbidden();
        }
    }

    public function test_manager_dapat_mengubah_verification(): void
    {
        $admin = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id, 'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Draft->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $this->actingAs($admin)->put(route('library.update', $m), [
            'title' => $m->title,
            'description' => $m->description,
            'scope' => 'core',
            'material_type' => $m->material_type->value,
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => 'draft',
            'verification_status' => 'pending_review',
            'validity_status' => 'unknown',
            'content' => 'Konten verifikasi uji.',
        ])->assertRedirect();
        $m->refresh();
        $this->assertSame(LearningVerificationStatus::PendingReview, $m->verification_status);

        $this->actingAs($admin)->put(route('library.update', $m), [
            'title' => $m->title,
            'description' => $m->description,
            'scope' => 'core',
            'material_type' => $m->material_type->value,
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => 'draft',
            'verification_status' => 'verified',
            'validity_status' => 'active',
            'content' => 'Konten verifikasi uji.',
        ])->assertRedirect();
        $m->refresh();
        $this->assertTrue($m->verification_status === LearningVerificationStatus::Verified);
    }

    public function test_verification_non_verified_tidak_dapat_publish(): void
    {
        $admin = $this->user('pemilik');
        foreach ([LearningVerificationStatus::Draft, LearningVerificationStatus::PendingReview, LearningVerificationStatus::Rejected, LearningVerificationStatus::NeedsUpdate] as $status) {
            [$level, $category] = $this->levelAndCategory();
            $m = LearningMaterial::factory()->core()->create([
                'learning_level_id' => $level->id, 'learning_category_id' => $category->id,
                'verification_status' => $status->value,
                'validity_status' => LearningValidityStatus::Active->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
            ]);
            LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
            $this->actingAs($admin)->post(route('library.publish', $m))->assertSessionHasErrors('publication_status');
        }
    }

    // ---- Validity ----
    public function test_validity_hanya_active_dapat_publish_core(): void
    {
        $admin = $this->user('admin');
        foreach ([LearningValidityStatus::Unknown, LearningValidityStatus::Superseded, LearningValidityStatus::Revoked, LearningValidityStatus::Expired] as $validity) {
            [$level, $category] = $this->levelAndCategory();
            $m = LearningMaterial::factory()->core()->create([
                'learning_level_id' => $level->id, 'learning_category_id' => $category->id,
                'verification_status' => LearningVerificationStatus::Verified->value,
                'validity_status' => $validity->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
            ]);
            LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
            $this->actingAs($admin)->post(route('library.publish', $m))->assertSessionHasErrors('publication_status');
        }

        [$level, $category] = $this->levelAndCategory();
        $ok = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id, 'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $ok->id, 'source_name' => 'S', 'publisher' => 'P']);
        $this->actingAs($admin)->post(route('library.publish', $ok))->assertRedirect();
        $ok->refresh();
        $this->assertTrue($ok->isCurrent());
    }

    // ---- Publication ----
    public function test_custom_dapat_publish_tanpa_provenance(): void
    {
        $admin = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id, 'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Draft->value,
            'validity_status' => LearningValidityStatus::Unknown->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        $this->actingAs($admin)->post(route('library.publish', $m))->assertRedirect();
        $m->refresh();
        $this->assertTrue($m->isPublished());
    }

    public function test_publish_mengisi_published_at_unpublish_mengosongkan(): void
    {
        $admin = $this->user('admin');
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id, 'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        $this->assertNull($m->published_at);
        $this->actingAs($admin)->post(route('library.publish', $m))->assertRedirect();
        $m->refresh();
        $this->assertNotNull($m->published_at);
        $this->actingAs($admin)->post(route('library.unpublish', $m))->assertRedirect();
        $m->refresh();
        $this->assertNull($m->published_at);
        $this->assertTrue(LearningMaterialSource::factory()->create(['learning_material_id' => $m->id])->material->is($m));
    }

    // ---- Visibility ----
    public function test_visibility_current_only_untuk_user_biasa(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $currentCore = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id, 'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'title' => 'Core Current Unik',
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $currentCore->id, 'source_name' => 'S', 'publisher' => 'P']);
        $currentCore->publish();

        $expiredCore = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id, 'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'title' => 'Core Expired Unik',
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $expiredCore->id, 'source_name' => 'S', 'publisher' => 'P']);
        $expiredCore->publish();
        \Illuminate\Support\Facades\DB::table('learning_materials')->where('id', $expiredCore->id)->update(['validity_status' => LearningValidityStatus::Expired->value]);
        $expiredCore->refresh();

        // Non-current tetap di DB tetapi tidak muncul di index user biasa (current scope)
        $pegawai = $this->user('pegawai');
        $admin = $this->user('admin');

        $this->actingAs($pegawai)->get(route('library.index'))->assertSee('Core Current Unik')->assertDontSee('Core Expired Unik');
        $this->actingAs($admin)->get(route('library.index'))->assertSee('Core Current Unik')->assertSee('Core Expired Unik');

        $this->assertTrue($currentCore->isCurrent());
        $this->assertFalse($expiredCore->isCurrent());
        $this->assertSame(1, LearningMaterial::current()->where('id', $currentCore->id)->count());
        $this->assertSame(0, LearningMaterial::current()->where('id', $expiredCore->id)->count());

        // unpublished tetap tersimpan, tidak current
        $draftCustom = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id, 'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'title' => 'Draft Custom Unik',
        ]);
        $this->actingAs($pegawai)->get(route('library.index'))->assertDontSee('Draft Custom Unik');
        $this->assertFalse($draftCustom->isCurrent());
    }

    public function test_provenance_tidak_terhapus_saat_unpublish(): void
    {
        $admin = $this->user('admin');
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id, 'learning_category_id' => $category->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        $source = LearningMaterialSource::factory()->create(['learning_material_id' => $m->id]);
        $m->publish();
        $this->actingAs($admin)->post(route('library.unpublish', $m))->assertRedirect();
        $this->assertDatabaseHas('learning_material_sources', ['id' => $source->id]);
    }

    // ---- Authorization matrix ----
    public function test_authorization_matrix_manage(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->create(['learning_level_id' => $level->id, 'learning_category_id' => $category->id]);

        foreach (['admin', 'admin_library', 'pemilik'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get(route('library.source.edit', $m))->assertOk();
            $this->actingAs($u)->post(route('library.publish', $m))->assertRedirect();
            $m->unpublish();
        }

        foreach (['atasan', 'pegawai'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get(route('library.source.edit', $m))->assertForbidden();
            $this->actingAs($u)->put(route('library.source.update', $m), ['source_name' => 'X', 'publisher' => 'Y'])->assertForbidden();
            $this->actingAs($u)->post(route('library.publish', $m))->assertForbidden();
            $this->actingAs($u)->post(route('library.unpublish', $m))->assertForbidden();
        }
    }

    public function test_source_url_validasi(): void
    {
        $admin = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->create(['learning_level_id' => $level->id, 'learning_category_id' => $category->id]);
        $this->actingAs($admin)->put(route('library.source.update', $m), [
            'source_name' => 'Sumber',
            'publisher' => 'Penerbit',
            'source_url' => 'not-a-url',
        ])->assertSessionHasErrors('source_url');
    }
}
