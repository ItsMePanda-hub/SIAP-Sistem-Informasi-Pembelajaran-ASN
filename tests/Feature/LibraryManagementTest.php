<?php

namespace Tests\Feature;

use App\Enums\LearningPublicationStatus;
use App\Enums\LearningVerificationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function levelAndCategory(): array
    {
        $level = LearningLevel::factory()->create();
        $category = LearningCategory::factory()->create();
        return [$level, $category];
    }

    private function payload(?LearningLevel $level = null, ?LearningCategory $category = null, array $over = []): array
    {
        [$l, $c] = [$level, $category];
        if (! $l || ! $c) {
            [$l2, $c2] = $this->levelAndCategory();
            $l = $l ?? $l2;
            $c = $c ?? $c2;
        }
        $base = array_merge([
            'title' => 'Materi Uji '.fake()->word(),
            'description' => 'Deskripsi uji',
            'scope' => 'custom',
            'material_type' => 'native',
            'learning_level_id' => $l->id,
            'learning_category_id' => $c->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'content' => 'Konten artikel native uji yang cukup panjang.',
        ], $over);

        if (($base['material_type'] ?? 'native') === 'pdf' && ! array_key_exists('pdf_file', $base)) {
            unset($base['content']);
        }

        return $base;
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_manager_dapat_membuka_library(): void
    {
        foreach (['admin', 'admin_library', 'pemilik'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get(route('library.index'))->assertOk()->assertSee('SIAP Library');
        }
    }

    public function test_manager_dapat_create_material(): void
    {
        foreach (['admin', 'admin_library', 'pemilik'] as $role) {
            $u = $this->user($role);
            [$level, $cat] = $this->levelAndCategory();
            $payload = $this->payload($level, $cat);
            $this->actingAs($u)->post(route('library.store'), $payload)->assertRedirect(route('library.index'));
            $this->assertDatabaseHas('learning_materials', ['title' => $payload['title'], 'uploaded_by' => $u->id]);
        }
    }

    public function test_manager_dapat_update_material(): void
    {
        $u = $this->user('admin_library');
        [$level, $cat] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->create(['learning_level_id' => $level->id, 'learning_category_id' => $cat->id, 'uploaded_by' => $u->id]);
        $payload = $this->payload($level, $cat, ['title' => 'Judul Update Khusus']);
        $this->actingAs($u)->put(route('library.update', $m), $payload)->assertRedirect(route('library.index'));
        $this->assertDatabaseHas('learning_materials', ['id' => $m->id, 'title' => 'Judul Update Khusus']);
    }

    public function test_manager_dapat_delete_material(): void
    {
        $u = $this->user('pemilik');
        [$level, $cat] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->create(['learning_level_id' => $level->id, 'learning_category_id' => $cat->id]);
        $source = \App\Models\LearningMaterialSource::factory()->create(['learning_material_id' => $m->id]);
        $this->actingAs($u)->delete(route('library.destroy', $m))->assertRedirect(route('library.index'));
        $this->assertDatabaseMissing('learning_materials', ['id' => $m->id]);
        $this->assertDatabaseMissing('learning_material_sources', ['id' => $source->id]);
    }

    public function test_atasan_pegawai_tidak_dapat_create(): void
    {
        foreach (['atasan', 'pegawai'] as $role) {
            $u = $this->user($role);
            [$level, $cat] = $this->levelAndCategory();
            $this->actingAs($u)->get(route('library.create'))->assertForbidden();
            $this->actingAs($u)->post(route('library.store'), $this->payload($level, $cat))->assertForbidden();
        }
    }

    public function test_atasan_pegawai_tidak_dapat_update_delete(): void
    {
        foreach (['atasan', 'pegawai'] as $role) {
            $u = $this->user($role);
            [$level, $cat] = $this->levelAndCategory();
            $m = LearningMaterial::factory()->custom()->create(['learning_level_id' => $level->id, 'learning_category_id' => $cat->id, 'publication_status' => 'draft']);
            $this->actingAs($u)->get(route('library.edit', $m))->assertForbidden();
            $this->actingAs($u)->put(route('library.update', $m), $this->payload($level, $cat))->assertForbidden();
            $this->actingAs($u)->delete(route('library.destroy', $m))->assertForbidden();
        }
    }

    public function test_visibility_published_unpublished(): void
    {
        [$level, $cat] = $this->levelAndCategory();
        $pub = LearningMaterial::factory()->custom()->create(['learning_level_id' => $level->id, 'learning_category_id' => $cat->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Materi Published Unik']);
        $draft = LearningMaterial::factory()->custom()->create(['learning_level_id' => $level->id, 'learning_category_id' => $cat->id, 'publication_status' => LearningPublicationStatus::Draft->value, 'title' => 'Materi Draft Unik']);
        $pegawai = $this->user('pegawai');
        $atasan = $this->user('atasan');
        $admin = $this->user('admin');

        $this->actingAs($pegawai)->get(route('library.index'))->assertSee('Materi Published Unik')->assertDontSee('Materi Draft Unik');
        $this->actingAs($atasan)->get(route('library.index'))->assertSee('Materi Published Unik')->assertDontSee('Materi Draft Unik');
        $this->actingAs($admin)->get(route('library.index'))->assertSee('Materi Published Unik')->assertSee('Materi Draft Unik');
        $this->assertTrue($pegawai->can('view', $pub));
        $this->assertFalse($pegawai->can('view', $draft));
    }

    public function test_validation_title_required(): void
    {
        $u = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();
        $payload = $this->payload($level, $cat, ['title' => '']);
        $this->actingAs($u)->post(route('library.store'), $payload)->assertSessionHasErrors('title');
    }

    public function test_validation_invalid_level_category_enum_ditolak(): void
    {
        $u = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();
        $this->actingAs($u)->post(route('library.store'), $this->payload($level, $cat, ['learning_level_id' => 99999]))->assertSessionHasErrors('learning_level_id');
        $this->actingAs($u)->post(route('library.store'), $this->payload($level, $cat, ['learning_category_id' => 99999]))->assertSessionHasErrors('learning_category_id');
        $this->actingAs($u)->post(route('library.store'), $this->payload($level, $cat, ['scope' => 'invalid']))->assertSessionHasErrors('scope');
        $this->actingAs($u)->post(route('library.store'), $this->payload($level, $cat, ['material_type' => 'invalid']))->assertSessionHasErrors('material_type');
        $this->actingAs($u)->post(route('library.store'), $this->payload($level, $cat, ['publication_status' => 'invalid']))->assertSessionHasErrors('publication_status');
    }

    public function test_uploaded_by_dari_auth_bukan_request(): void
    {
        $u = $this->user('admin_library');
        $other = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();
        $payload = $this->payload($level, $cat);
        $payload['uploaded_by'] = $other->id;
        $this->actingAs($u)->post(route('library.store'), $payload)->assertRedirect();
        $m = LearningMaterial::where('title', $payload['title'])->firstOrFail();
        $this->assertSame($u->id, $m->uploaded_by);
        $this->assertNotSame($other->id, $m->uploaded_by);
    }

    public function test_core_published_tanpa_verified_ditolak(): void
    {
        $u = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();
        $payload = $this->payload($level, $cat, ['scope' => 'core', 'publication_status' => 'published', 'verification_status' => 'draft']);
        $this->actingAs($u)->post(route('library.store'), $payload)->assertSessionHasErrors('publication_status');
    }

    public function test_search_dan_filter_dan_pagination(): void
    {
        $admin = $this->user('admin');
        [$level1, $cat1] = $this->levelAndCategory();
        $level2 = LearningLevel::factory()->create();
        LearningMaterial::factory()->custom()->create(['learning_level_id' => $level1->id, 'learning_category_id' => $cat1->id, 'title' => 'Judul Unik Alpha', 'description' => 'deskripsi khusus', 'scope' => 'custom', 'publication_status' => 'draft']);
        LearningMaterial::factory()->custom()->create(['learning_level_id' => $level2->id, 'learning_category_id' => $cat1->id, 'title' => 'Judul Lain Beta', 'scope' => 'core', 'publication_status' => 'draft', 'verification_status' => 'verified', 'validity_status' => 'active']);
        $this->actingAs($admin)->get(route('library.index', ['q' => 'Alpha']))->assertSee('Judul Unik Alpha')->assertDontSee('Judul Lain Beta');
        $this->actingAs($admin)->get(route('library.index', ['scope' => 'custom']))->assertSee('Judul Unik Alpha')->assertDontSee('Judul Lain Beta');
        $this->actingAs($admin)->get(route('library.index', ['learning_level_id' => $level1->id]))->assertSee('Judul Unik Alpha')->assertDontSee('Judul Lain Beta');
        for ($i = 0; $i < 13; $i++) {
            LearningMaterial::factory()->custom()->create(['learning_level_id' => $level1->id, 'learning_category_id' => $cat1->id, 'publication_status' => 'draft', 'title' => "Bulk {$i}"]);
        }
        $resp = $this->actingAs($admin)->get(route('library.index'));
        $resp->assertOk();
        $this->assertStringContainsString('pagination', strtolower($resp->getContent()));
    }
}
