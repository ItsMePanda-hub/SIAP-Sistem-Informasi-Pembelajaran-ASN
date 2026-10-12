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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryContentHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function lc(): array
    {
        return [LearningLevel::factory()->create(), LearningCategory::factory()->create()];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_native_content_escaped(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
            'content' => '<b>bold</b>',
        ]);
        $resp = $this->actingAs($this->user('pegawai'))->get(route('library.read', $m));
        $resp->assertOk();
        $this->assertStringNotContainsString('<b>bold</b>', $resp->getContent());
        $this->assertStringContainsString(e('<b>bold</b>'), $resp->getContent());
    }

    public function test_script_payload_tidak_dieksekusi(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
            'content' => '<script>alert(1)</script>',
        ]);
        $html = $this->actingAs($this->user('pegawai'))->get(route('library.read', $m))->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString(e('<script>alert(1)</script>'), $html);
    }

    public function test_img_onerror_payload_escaped(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
            'content' => '<img src=x onerror=alert(1)>',
        ]);
        $html = $this->actingAs($this->user('pegawai'))->get(route('library.read', $m))->getContent();
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString(e('<img src=x onerror=alert(1)>'), $html);
    }

    public function test_native_panjang_tetap_dibaca(): void
    {
        [$l, $c] = $this->lc();
        $long = str_repeat('Lorem ipsum dolor sit amet. ', 500);
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
            'content' => $long, 'title' => 'Panjang Test',
        ]);
        $this->actingAs($this->user('pegawai'))->get(route('library.read', $m))->assertOk()->assertSee('Lorem ipsum');
    }

    public function test_newline_dipertahankan(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
            'content' => "baris satu\nbaris dua\nbaris tiga",
        ]);
        $html = $this->actingAs($this->user('pegawai'))->get(route('library.read', $m))->getContent();
        $this->assertStringContainsString('baris satu', $html);
        $this->assertStringContainsString('baris dua', $html);
        $this->assertStringContainsString('baris tiga', $html);
    }

    public function test_native_null_tidak_fatal(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ]);
        DB::table('learning_materials')->where('id', $m->id)->update(['content' => null]);
        $m->refresh();
        $this->actingAs($this->user('admin'))->get(route('library.read', $m))->assertOk();
    }

    public function test_pdf_file_hilang_tidak_bocor_path(): void
    {
        Storage::fake('public');
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->pdf()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ]);
        Storage::disk('public')->delete($m->file_path);
        $resp = $this->actingAs($this->user('pegawai'))->get(route('library.pdf', $m));
        $resp->assertNotFound();
        $this->assertStringNotContainsString($m->file_path, $resp->getContent());
        $this->assertStringNotContainsString('storage/app', $resp->getContent());
    }

    public function test_reader_pdf_hilang_tetap_aman(): void
    {
        Storage::fake('public');
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->pdf()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ]);
        Storage::disk('public')->delete($m->file_path);
        $this->actingAs($this->user('admin'))->get(route('library.read', $m))->assertOk();
    }

    public function test_pdf_tidak_arbitrary_path(): void
    {
        Storage::fake('public');
        $m = LearningMaterial::factory()->custom()->pdf()->create([
            'learning_level_id' => $this->lc()[0]->id, 'learning_category_id' => $this->lc()[1]->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ]);
        Storage::disk('public')->put($m->file_path, '%PDF fake');
        $resp = $this->actingAs($this->user('pegawai'))->get(route('library.read', $m).'?file_path=../../.env');
        $resp->assertOk();
        $this->assertStringNotContainsString('../../.env', $m->fresh()->file_path ?? '');
    }

    public function test_pdf_hanya_file_milik_material(): void
    {
        Storage::fake('public');
        [$l, $c] = $this->lc();
        $m1 = LearningMaterial::factory()->custom()->pdf()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now()]);
        $m2 = LearningMaterial::factory()->custom()->pdf()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now()]);
        Storage::disk('public')->put($m1->file_path, 'PDF-ONE');
        Storage::disk('public')->put($m2->file_path, 'PDF-TWO');
        $resp = $this->actingAs($this->user('pegawai'))->get(route('library.pdf', $m1));
        $resp->assertOk();
        $this->assertNotEquals($m1->file_path, $m2->file_path);
        $this->assertStringNotContainsString($m2->file_path, $resp->getContent());
    }

    public function test_pdf_butuh_authorization(): void
    {
        Storage::fake('public');
        $m = LearningMaterial::factory()->custom()->pdf()->create([
            'learning_level_id' => $this->lc()[0]->id, 'learning_category_id' => $this->lc()[1]->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ]);
        Storage::disk('public')->put($m->file_path, '%PDF');
        $this->get(route('library.pdf', $m))->assertRedirect(route('login'));
    }

    public function test_pdf_content_type(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        [$l, $c] = $this->lc();
        $pdf = UploadedFile::fake()->create('ct.pdf', 50, 'application/pdf');
        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'CT PDF', 'scope' => 'custom', 'material_type' => 'pdf',
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => 'published', 'verification_status' => 'draft', 'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();
        $m = LearningMaterial::where('title', 'CT PDF')->firstOrFail();
        $this->actingAs($this->user('pegawai'))->get(route('library.pdf', $m))->assertHeader('content-type', 'application/pdf');
    }

    public function test_unpublished_tidak_dapat_dibaca_consumer(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value]);
        foreach (['atasan', 'pegawai'] as $r) {
            $this->actingAs($this->user($r))->get(route('library.read', $m))->assertForbidden();
        }
    }

    public function test_expired_revoked_superseded_tidak_current(): void
    {
        foreach ([LearningValidityStatus::Expired, LearningValidityStatus::Revoked, LearningValidityStatus::Superseded] as $v) {
            [$l, $c] = $this->lc();
            $m = LearningMaterial::factory()->core()->create([
                'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
                'material_type' => 'native', 'content' => 'isi',
                'verification_status' => LearningVerificationStatus::Verified->value,
                'validity_status' => LearningValidityStatus::Active->value, 'publication_status' => LearningPublicationStatus::Draft->value,
            ]);
            LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
            $m->publish();
            DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => $v->value]);
            $m->refresh();
            $this->assertFalse($m->isCurrent());
            $this->actingAs($this->user('pegawai'))->get(route('library.read', $m))->assertForbidden();
        }
    }

    public function test_native_ke_pdf_konsisten(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin_library');
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id]);
        $pdf = UploadedFile::fake()->create('to-pdf.pdf', 50, 'application/pdf');
        $this->actingAs($manager)->put(route('library.update', $m), [
            'title' => $m->title, 'scope' => 'custom', 'material_type' => 'pdf',
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => 'draft', 'verification_status' => 'draft', 'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();
        $m->refresh();
        $this->assertTrue($m->isPdf());
        $this->assertNull($m->content);
        Storage::disk('public')->assertExists($m->file_path);
    }

    public function test_pdf_ke_native_konsisten(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        [$l, $c] = $this->lc();
        $pdf = UploadedFile::fake()->create('to-native.pdf', 50, 'application/pdf');
        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'Hardening PDF2Native', 'scope' => 'custom', 'material_type' => 'pdf',
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => 'draft', 'verification_status' => 'draft', 'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();
        $m = LearningMaterial::where('title', 'Hardening PDF2Native')->firstOrFail();
        $old = $m->file_path;
        $this->actingAs($manager)->put(route('library.update', $m), [
            'title' => $m->title, 'scope' => 'custom', 'material_type' => 'native',
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => 'draft', 'verification_status' => 'draft', 'validity_status' => 'unknown',
            'content' => 'konten baru native',
        ])->assertRedirect();
        $m->refresh();
        $this->assertTrue($m->isNative());
        $this->assertNull($m->file_path);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_replacement_tidak_tinggal_old(): void
    {
        Storage::fake('public');
        $manager = $this->user('pemilik');
        [$l, $c] = $this->lc();
        $pdf1 = UploadedFile::fake()->create('old.pdf', 50, 'application/pdf');
        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'Hardening Replace', 'scope' => 'custom', 'material_type' => 'pdf',
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => 'draft', 'verification_status' => 'draft', 'validity_status' => 'unknown',
            'pdf_file' => $pdf1,
        ])->assertRedirect();
        $m = LearningMaterial::where('title', 'Hardening Replace')->firstOrFail();
        $old = $m->file_path;
        $pdf2 = UploadedFile::fake()->create('new.pdf', 50, 'application/pdf');
        $this->actingAs($manager)->put(route('library.update', $m), [
            'title' => $m->title, 'scope' => 'custom', 'material_type' => 'pdf',
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => 'draft', 'verification_status' => 'draft', 'validity_status' => 'unknown',
            'pdf_file' => $pdf2,
        ])->assertRedirect();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($m->fresh()->file_path);
    }

    public function test_delete_pdf_bersihkan_file(): void
    {
        Storage::fake('public');
        $manager = $this->user('admin');
        [$l, $c] = $this->lc();
        $pdf = UploadedFile::fake()->create('del.pdf', 50, 'application/pdf');
        $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'Hardening Delete PDF', 'scope' => 'custom', 'material_type' => 'pdf',
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => 'draft', 'verification_status' => 'draft', 'validity_status' => 'unknown',
            'pdf_file' => $pdf,
        ])->assertRedirect();
        $m = LearningMaterial::where('title', 'Hardening Delete PDF')->firstOrFail();
        $path = $m->file_path;
        $this->actingAs($manager)->delete(route('library.destroy', $m))->assertRedirect();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_native_tidak_error(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id]);
        $this->actingAs($this->user('admin'))->delete(route('library.destroy', $m))->assertRedirect();
        $this->assertDatabaseMissing('learning_materials', ['id' => $m->id]);
    }

    public function test_manager_tetap_allowed_guest_redirect(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value]);
        $this->get(route('library.read', $m))->assertRedirect(route('login'));
        foreach (['admin', 'admin_library', 'pemilik'] as $r) {
            $this->actingAs($this->user($r))->get(route('library.read', $m))->assertOk();
        }
    }

    public function test_reader_tidak_ubah_source(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now()]);
        $source = LearningMaterialSource::factory()->create(['learning_material_id' => $m->id]);
        $this->actingAs($this->user('pegawai'))->get(route('library.read', $m))->assertOk();
        $this->assertDatabaseHas('learning_material_sources', ['id' => $source->id, 'source_name' => $source->source_name]);
    }
}
