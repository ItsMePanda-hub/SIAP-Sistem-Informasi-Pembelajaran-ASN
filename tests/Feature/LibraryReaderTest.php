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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryReaderTest extends TestCase
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

    private function nativeCurrent(array $over = []): LearningMaterial
    {
        [$level, $category] = $this->levelAndCategory();
        return LearningMaterial::factory()->custom()->native()->create(array_merge([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Published->value,
            'published_at' => now(),
            'title' => 'Native Current '.fake()->word(),
            'content' => 'Isi artikel native untuk reader test.',
        ], $over));
    }

    private function pdfCurrent(array $over = []): LearningMaterial
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->pdf()->create(array_merge([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Published->value,
            'published_at' => now(),
            'title' => 'PDF Current '.fake()->word(),
        ], $over));
        Storage::disk('public')->put($m->file_path, '%PDF-1.4 fake');
        return $m->fresh();
    }

    public function test_authenticated_dapat_membuka_native_published_current(): void
    {
        Storage::fake('public');
        $m = $this->nativeCurrent();
        $user = $this->user('pegawai');
        $this->actingAs($user)->get(route('library.read', $m))->assertOk();
    }

    public function test_native_reader_menampilkan_title(): void
    {
        $m = $this->nativeCurrent(['title' => 'Judul Native Unik XYZ']);
        $user = $this->user('pegawai');
        $this->actingAs($user)->get(route('library.read', $m))->assertSee('Judul Native Unik XYZ');
    }

    public function test_native_reader_menampilkan_content(): void
    {
        $m = $this->nativeCurrent(['content' => 'Paragraf unik ABC untuk reader.']);
        $user = $this->user('pegawai');
        $this->actingAs($user)->get(route('library.read', $m))->assertSee('Paragraf unik ABC untuk reader.');
    }

    public function test_native_content_tidak_xss(): void
    {
        $payload = '<script>alert("xss")</script><b>bold</b>';
        $m = $this->nativeCurrent(['content' => $payload]);
        $user = $this->user('pegawai');
        $resp = $this->actingAs($user)->get(route('library.read', $m));
        $resp->assertOk();
        $html = $resp->getContent();
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
        $this->assertStringContainsString(e('<script>alert("xss")</script>'), $html);
        $this->assertStringNotContainsString('<b>bold</b>', $html);
        $this->assertStringContainsString(e('<b>bold</b>'), $html);
    }

    public function test_authenticated_dapat_membuka_pdf_published_current(): void
    {
        Storage::fake('public');
        $m = $this->pdfCurrent();
        $user = $this->user('pegawai');
        $this->actingAs($user)->get(route('library.read', $m))->assertOk();
    }

    public function test_pdf_reader_menggunakan_controlled_pdf_route(): void
    {
        Storage::fake('public');
        $m = $this->pdfCurrent(['title' => 'PDF Reader Route Test']);
        $user = $this->user('pegawai');
        $resp = $this->actingAs($user)->get(route('library.read', $m));
        $resp->assertOk();
        $this->assertStringContainsString(route('library.pdf', $m), $resp->getContent());
        $this->assertStringContainsString('<iframe', $resp->getContent());
    }

    public function test_reader_tidak_menggunakan_public_storage_url(): void
    {
        Storage::fake('public');
        $m = $this->pdfCurrent();
        $user = $this->user('pegawai');
        $resp = $this->actingAs($user)->get(route('library.read', $m));
        $html = $resp->getContent();
        $this->assertStringNotContainsString('/storage/library-pdfs', $html);
        $this->assertStringNotContainsString('storage/app/public', $html);
    }

    public function test_guest_tidak_dapat_membuka_reader(): void
    {
        $m = $this->nativeCurrent();
        $this->get(route('library.read', $m))->assertRedirect(route('login'));
    }

    public function test_atasan_pegawai_tidak_dapat_membaca_unpublished(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'title' => 'Unpublished Reader',
        ]);
        foreach (['atasan', 'pegawai'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get(route('library.read', $m))->assertForbidden();
        }
        $manager = $this->user('admin');
        $this->actingAs($manager)->get(route('library.read', $m))->assertOk();
    }

    public function test_atasan_tidak_dapat_membaca_expired(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'material_type' => 'native',
            'content' => 'Isi expired',
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
        $m->publish();
        \Illuminate\Support\Facades\DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => LearningValidityStatus::Expired->value]);
        $m->refresh();
        $this->assertFalse($m->isCurrent());
        foreach (['atasan', 'pegawai'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get(route('library.read', $m))->assertForbidden();
        }
    }

    public function test_pegawai_tidak_dapat_membaca_revoked(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'material_type' => 'native',
            'content' => 'Isi revoked',
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
        $m->publish();
        \Illuminate\Support\Facades\DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => LearningValidityStatus::Revoked->value]);
        $m->refresh();
        foreach (['atasan', 'pegawai'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get(route('library.read', $m))->assertForbidden();
        }
    }

    public function test_atasan_tidak_dapat_membaca_superseded(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'material_type' => 'native',
            'content' => 'Isi superseded',
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
        $m->publish();
        \Illuminate\Support\Facades\DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => LearningValidityStatus::Superseded->value]);
        $m->refresh();
        foreach (['atasan', 'pegawai'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get(route('library.read', $m))->assertForbidden();
        }
    }

    public function test_admin_dapat_membaca_sesuai_super_admin(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('library.read', $m))->assertOk();
    }

    public function test_admin_library_dapat_membaca(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        $u = $this->user('admin_library');
        $this->actingAs($u)->get(route('library.read', $m))->assertOk();
    }

    public function test_pemilik_dapat_membaca(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        $u = $this->user('pemilik');
        $this->actingAs($u)->get(route('library.read', $m))->assertOk();
    }

    public function test_reader_menggunakan_model_binding(): void
    {
        $user = $this->user('admin');
        $this->actingAs($user)->get('/perpustakaan/999999/baca')->assertNotFound();
        $this->actingAs($user)->get('/perpustakaan/abc/baca')->assertNotFound();
    }

    public function test_tidak_ada_arbitrary_file_path_parameter(): void
    {
        Storage::fake('public');
        $m = $this->nativeCurrent(['title' => 'Arbitrary Path Check Reader']);
        $user = $this->user('pegawai');
        $resp = $this->actingAs($user)->get(route('library.read', $m).'?file_path=../../.env&file=../../storage/app/.env');
        $resp->assertOk();
        $this->assertStringNotContainsString('../../.env', $m->fresh()->file_path ?? '');
        $resp->assertSee('Arbitrary Path Check Reader');
    }

    public function test_invalid_material_type_ditangani_aman(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        \Illuminate\Support\Facades\DB::table('learning_materials')->where('id', $m->id)->update(['material_type' => 'invalid_type']);
        $m->refresh();
        $user = $this->user('admin');
        $this->actingAs($user)->get(route('library.read', $m))->assertNotFound();
    }

    public function test_index_memiliki_cta_baca_ke_reader(): void
    {
        Storage::fake('public');
        $m = $this->nativeCurrent(['title' => 'CTA Baca Test']);
        $user = $this->user('pegawai');
        $resp = $this->actingAs($user)->get(route('library.index'));
        $resp->assertOk();
        $this->assertStringContainsString(route('library.read', $m), $resp->getContent());
        $this->assertStringContainsString('Baca', $resp->getContent());
        $this->assertStringNotContainsString('/storage/library-pdfs', $resp->getContent());
    }
}
