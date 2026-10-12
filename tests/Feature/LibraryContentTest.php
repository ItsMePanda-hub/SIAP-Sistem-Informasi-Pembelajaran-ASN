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

class LibraryContentTest extends TestCase
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

    private function payloadNative(?LearningLevel $level = null, ?LearningCategory $category = null, array $over = []): array
    {
        [$l, $c] = [$level, $category];
        if (! $l || ! $c) {
            [$l2, $c2] = $this->levelAndCategory();
            $l = $l ?? $l2;
            $c = $c ?? $c2;
        }
        return array_merge([
            'title' => 'Native Uji '.fake()->word(),
            'description' => 'Deskripsi',
            'scope' => 'custom',
            'material_type' => 'native',
            'learning_level_id' => $l->id,
            'learning_category_id' => $c->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'content' => 'Konten artikel native yang panjang untuk pengujian.',
        ], $over);
    }

    public function test_manager_dapat_membuat_native_dengan_content(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin_library');
        [$level, $cat] = $this->levelAndCategory();

        $this->actingAs($manager)->post(route('library.store'), $this->payloadNative($level, $cat))
            ->assertRedirect(route('library.index'));

        $this->assertDatabaseHas('learning_materials', ['title' => LearningMaterial::latest()->first()->title, 'material_type' => 'native']);
        $m = LearningMaterial::latest()->first();
        $this->assertTrue($m->hasContent());
        $this->assertFalse($m->hasFile());
        $this->assertNull($m->file_path);
    }

    public function test_native_menyimpan_content_tanpa_pdf(): void
    {
        Storage::fake('public');
        $manager = $this->user('pemilik');
        [$level, $cat] = $this->levelAndCategory();
        $payload = $this->payloadNative($level, $cat, ['content' => 'Artikel khusus isi native.']);

        $this->actingAs($manager)->post(route('library.store'), $payload)->assertRedirect();
        $m = LearningMaterial::where('title', $payload['title'])->firstOrFail();
        $this->assertSame('Artikel khusus isi native.', $m->content);
        $this->assertNull($m->file_path);
    }

    public function test_manager_dapat_membuat_pdf_valid(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();
        $pdf = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $payload = [
            'title' => 'PDF Uji Valid',
            'description' => 'Deskripsi',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ];

        $this->actingAs($manager)->post(route('library.store'), $payload)->assertRedirect();
        $m = LearningMaterial::where('title', 'PDF Uji Valid')->firstOrFail();
        Storage::disk('public')->assertExists($m->file_path);
        $this->assertSame('dokumen.pdf', $m->file_original_name);
        $this->assertSame('application/pdf', $m->file_mime);
        $this->assertNotNull($m->file_size);
        $this->assertNull($m->content);
        $this->assertTrue($m->isPdf());
        $this->assertTrue($m->hasFile());
    }

    public function test_invalid_non_pdf_ditolak(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();
        $bad = UploadedFile::fake()->create('doc.txt', 100, 'text/plain');

        $payload = [
            'title' => 'Bad PDF',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $bad,
        ];

        $this->actingAs($manager)->post(route('library.store'), $payload)->assertSessionHasErrors('pdf_file');
        $bad2 = UploadedFile::fake()->create('image.jpg', 100, 'image/jpeg');
        $payload['pdf_file'] = $bad2;
        $this->actingAs($manager)->post(route('library.store'), $payload)->assertSessionHasErrors('pdf_file');
    }

    public function test_pdf_tanpa_file_saat_create_ditolak(): void
    {
        $manager = $this->user('admin_library');
        [$level, $cat] = $this->levelAndCategory();

        $payload = [
            'title' => 'PDF Tanpa File',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
        ];

        $this->actingAs($manager)->post(route('library.store'), $payload)->assertSessionHasErrors('pdf_file');
    }

    public function test_native_content_required(): void
    {
        $manager = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();

        $payload = $this->payloadNative($level, $cat, ['content' => '']);
        $this->actingAs($manager)->post(route('library.store'), $payload)->assertSessionHasErrors('content');

        $payload2 = $this->payloadNative($level, $cat);
        unset($payload2['content']);
        $this->actingAs($manager)->post(route('library.store'), $payload2)->assertSessionHasErrors('content');
    }

    public function test_edit_metadata_pdf_tidak_memaksa_upload_ulang(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();
        $pdf = UploadedFile::fake()->create('orig.pdf', 100, 'application/pdf');

        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'PDF Edit Meta',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();

        $m = LearningMaterial::where('title', 'PDF Edit Meta')->firstOrFail();
        $oldPath = $m->file_path;
        Storage::disk('public')->assertExists($oldPath);

        $this->actingAs($manager)->put(route('library.update', $m), [
            'title' => 'PDF Edit Meta Updated',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
        ])->assertRedirect();

        $m->refresh();
        $this->assertSame('PDF Edit Meta Updated', $m->title);
        $this->assertSame($oldPath, $m->file_path);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_manager_dapat_mengganti_pdf_dan_file_lama_dibersihkan(): void
    {
        Storage::fake('public');
        $manager = $this->user('pemilik');
        [$level, $cat] = $this->levelAndCategory();
        $pdf1 = UploadedFile::fake()->create('lama.pdf', 100, 'application/pdf');
        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'PDF Ganti',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $pdf1,
        ])->assertRedirect();

        $m = LearningMaterial::where('title', 'PDF Ganti')->firstOrFail();
        $oldPath = $m->file_path;
        Storage::disk('public')->assertExists($oldPath);

        $pdf2 = UploadedFile::fake()->create('baru.pdf', 120, 'application/pdf');
        $this->actingAs($manager)->put(route('library.update', $m), [
            'title' => 'PDF Ganti',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $pdf2,
        ])->assertRedirect();

        $m->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($m->file_path);
        $this->assertSame('baru.pdf', $m->file_original_name);
    }

    public function test_hapus_pdf_material_membersihkan_file(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();
        $pdf = UploadedFile::fake()->create('hapus.pdf', 100, 'application/pdf');
        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'PDF Hapus',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();

        $m = LearningMaterial::where('title', 'PDF Hapus')->firstOrFail();
        $path = $m->file_path;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($manager)->delete(route('library.destroy', $m))->assertRedirect();
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('learning_materials', ['id' => $m->id]);
    }

    public function test_native_ke_pdf_berhasil(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin_library');
        [$level, $cat] = $this->levelAndCategory();

        $this->actingAs($manager)->post(route('library.store'), $this->payloadNative($level, $cat, ['title' => 'Native To PDF']))->assertRedirect();
        $m = LearningMaterial::where('title', 'Native To PDF')->firstOrFail();
        $this->assertTrue($m->isNative());
        $this->assertNotNull($m->content);

        $pdf = UploadedFile::fake()->create('convert.pdf', 100, 'application/pdf');
        $this->actingAs($manager)->put(route('library.update', $m), [
            'title' => 'Native To PDF',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();

        $m->refresh();
        $this->assertTrue($m->isPdf());
        $this->assertNull($m->content);
        Storage::disk('public')->assertExists($m->file_path);
    }

    public function test_pdf_ke_native_berhasil_dan_file_dihapus(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();
        $pdf = UploadedFile::fake()->create('asal.pdf', 100, 'application/pdf');
        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'PDF To Native',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();

        $m = LearningMaterial::where('title', 'PDF To Native')->firstOrFail();
        $oldPath = $m->file_path;
        Storage::disk('public')->assertExists($oldPath);

        $this->actingAs($manager)->put(route('library.update', $m), [
            'title' => 'PDF To Native',
            'scope' => 'custom',
            'material_type' => 'native',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'content' => 'Konten native baru setelah konversi.',
        ])->assertRedirect();

        $m->refresh();
        $this->assertTrue($m->isNative());
        $this->assertSame('Konten native baru setelah konversi.', $m->content);
        $this->assertNull($m->file_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_authenticated_dapat_akses_pdf_current(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        $reader = $this->user('pegawai');
        [$level, $cat] = $this->levelAndCategory();
        $pdf = UploadedFile::fake()->create('current.pdf', 100, 'application/pdf');

        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'PDF Current Akses',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'published',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();

        $m = LearningMaterial::where('title', 'PDF Current Akses')->firstOrFail();
        $this->assertTrue($m->isCurrent());

        $this->actingAs($reader)->get(route('library.pdf', $m))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($manager)->get(route('library.pdf', $m))->assertOk();
    }

    public function test_tidak_dapat_akses_pdf_unpublished(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        $reader = $this->user('pegawai');
        [$level, $cat] = $this->levelAndCategory();
        $pdf = UploadedFile::fake()->create('draft.pdf', 100, 'application/pdf');

        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'PDF Draft Tidak Akses',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();

        $m = LearningMaterial::where('title', 'PDF Draft Tidak Akses')->firstOrFail();
        $this->actingAs($reader)->get(route('library.pdf', $m))->assertForbidden();
        $this->actingAs($manager)->get(route('library.pdf', $m))->assertOk();
    }

    public function test_tidak_dapat_akses_pdf_expired_revoked_superseded(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        $reader = $this->user('pegawai');

        foreach ([LearningValidityStatus::Expired, LearningValidityStatus::Revoked, LearningValidityStatus::Superseded] as $status) {
            [$level, $cat] = $this->levelAndCategory();
            $m = LearningMaterial::factory()->core()->create([
                'learning_level_id' => $level->id,
                'learning_category_id' => $cat->id,
                'material_type' => 'pdf',
                'scope' => 'core',
                'verification_status' => LearningVerificationStatus::Verified->value,
                'validity_status' => LearningValidityStatus::Active->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
                'content' => null,
            ]);
            LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
            $m->refresh();
            // store file manually for core
            $path = 'library-pdfs/'.$m->id.'-test.pdf';
            Storage::disk('public')->put($path, '%PDF fake');
            $m->update(['file_path' => $path, 'file_original_name' => 'test.pdf', 'file_mime' => 'application/pdf', 'file_size' => 1000]);
            $m->publish();
            \Illuminate\Support\Facades\DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => $status->value]);
            $m->refresh();
            $this->assertFalse($m->isCurrent());
            $this->actingAs($reader)->get(route('library.pdf', $m))->assertForbidden();
            $managerRole = $manager->role;
            if (in_array($managerRole, ['admin', 'admin_library', 'pemilik'], true)) {
                $this->actingAs($manager)->get(route('library.pdf', $m))->assertOk();
            }
        }
    }

    public function test_tidak_berwenang_tidak_dapat_akses_pdf(): void
    {
        Storage::fake('public');
        [$level, $cat] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->pdf()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => LearningPublicationStatus::Published->value,
            'published_at' => now(),
        ]);
        Storage::disk('public')->put($m->file_path, '%PDF auth');

        $this->get(route('library.pdf', $m))->assertRedirect(route('login'));

        // path traversal attempt via extra query param should not affect
        $this->actingAs($this->user('pegawai'))->get(route('library.pdf', $m).'?file=../../.env')->assertOk();
        $this->assertNotSame('../../.env', $m->fresh()->file_path);
    }

    public function test_tidak_ada_arbitrary_path_traversal(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();
        $pdf = UploadedFile::fake()->create('safe.pdf', 100, 'application/pdf');
        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'PDF Traversal',
            'scope' => 'custom',
            'material_type' => 'pdf',
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => 'published',
            'verification_status' => 'draft',
            'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();
        $m = LearningMaterial::where('title', 'PDF Traversal')->firstOrFail();

        // ensure file_path is normalized under library-pdfs and not manipulable via request
        $this->assertStringStartsWith('library-pdfs/', $m->file_path);
        $other = LearningMaterial::factory()->custom()->pdf()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'publication_status' => LearningPublicationStatus::Published->value,
            'published_at' => now(),
        ]);
        Storage::disk('public')->put($other->file_path, '%PDF other');

        // requesting one material should not leak other's file
        $resp = $this->actingAs($this->user('pegawai'))->get(route('library.pdf', $m));
        $resp->assertOk();
        $this->assertStringNotContainsString($other->file_path, $resp->getContent());
    }

    public function test_policy_role_matrix_tetap_berlaku(): void
    {
        Storage::fake('public');
        [$level, $cat] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->create(['learning_level_id' => $level->id, 'learning_category_id' => $cat->id]);

        foreach (['admin', 'admin_library', 'pemilik'] as $role) {
            $u = $this->user($role);
            $this->assertTrue($u->can('create', LearningMaterial::class));
            $this->assertTrue($u->can('update', $m));
            $this->assertTrue($u->can('delete', $m));
        }
        foreach (['atasan', 'pegawai'] as $role) {
            $u = $this->user($role);
            $this->assertFalse($u->can('create', LearningMaterial::class));
            $this->assertFalse($u->can('update', $m));
            $this->assertFalse($u->can('delete', $m));
        }
    }

    public function test_provenance_lifecycle_tidak_rusak(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        [$level, $cat] = $this->levelAndCategory();

        $payload = $this->payloadNative($level, $cat, [
            'scope' => 'core',
            'publication_status' => 'published',
            'verification_status' => 'draft',
            'content' => 'Konten core draft',
        ]);
        $this->actingAs($manager)->post(route('library.store'), $payload)->assertSessionHasErrors('publication_status');

        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $cat->id,
            'material_type' => 'native',
            'content' => 'Konten core',
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
        $this->actingAs($manager)->post(route('library.publish', $m))->assertRedirect();
        $m->refresh();
        $this->assertTrue($m->isCurrent());
    }
}
