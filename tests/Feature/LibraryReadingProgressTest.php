<?php

namespace Tests\Feature;

use App\Enums\LearningPublicationStatus;
use App\Enums\LearningValidityStatus;
use App\Enums\LearningVerificationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialSource;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryReadingProgressTest extends TestCase
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

    private function currentNative(array $over = []): LearningMaterial
    {
        [$l, $c] = $this->lc();
        return LearningMaterial::factory()->custom()->native()->create(array_merge([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ], $over));
    }

    private function currentPdf(array $over = []): LearningMaterial
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->pdf()->create(array_merge([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ], $over));
        Storage::disk('public')->put($m->file_path, '%PDF fake');
        return $m->fresh();
    }

    // DATABASE
    public function test_user_dapat_memiliki_progress(): void
    {
        [$l, $c] = $this->lc();
        $u = $this->user('pegawai');
        $m = $this->currentNative();
        $p = ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 42, 'started_at' => now(), 'last_read_at' => now()]);
        $this->assertTrue($u->readingProgress()->where('id', $p->id)->exists());
    }

    public function test_material_dapat_memiliki_banyak_progress(): void
    {
        $m = $this->currentNative();
        $u1 = $this->user('pegawai');
        $u2 = $this->user('pegawai');
        ReadingProgress::create(['user_id' => $u1->id, 'learning_material_id' => $m->id, 'progress_percent' => 10]);
        ReadingProgress::create(['user_id' => $u2->id, 'learning_material_id' => $m->id, 'progress_percent' => 20]);
        $this->assertSame(2, $m->readingProgress()->count());
    }

    public function test_satu_user_material_hanya_satu_progress(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 10]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 20]);
    }

    public function test_duplicate_ditolak_via_unique(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 5]);
        try {
            DB::table('reading_progress')->insert(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 6, 'created_at' => now(), 'updated_at' => now()]);
            $this->fail('Expected unique violation');
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function test_relation_user_dan_material(): void
    {
        $u = $this->user('pegawai');
        $m = $this->currentNative();
        $p = ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 30]);
        $this->assertTrue($p->user->is($u));
        $this->assertTrue($p->learningMaterial->is($m));
    }

    // NATIVE
    public function test_authorized_dapat_menyimpan_progress(): void
    {
        Storage::fake('public');
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 42])
            ->assertOk()->assertJson(['progress_percent' => 42, 'completed' => false]);
        $this->assertDatabaseHas('reading_progress', ['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 42]);
    }

    public function test_guest_tidak_dapat_menyimpan_progress(): void
    {
        $m = $this->currentNative();
        $this->post(route('library.progress.update', $m), ['progress_percent' => 50])->assertRedirect(route('login'));
    }

    public function test_tidak_dapat_progress_untuk_tidak_bisa_dibaca(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value]);
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 30])->assertForbidden();
    }

    public function test_progress_0_diterima(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 0])->assertOk();
        $this->assertDatabaseHas('reading_progress', ['user_id' => $u->id, 'progress_percent' => 0]);
    }

    public function test_progress_100_diterima(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        Carbon::setTestNow($now = now());
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 100])->assertOk()->assertJson(['progress_percent' => 100, 'completed' => true]);
        $p = ReadingProgress::where('user_id', $u->id)->first();
        $this->assertNotNull($p->completed_at);
        Carbon::setTestNow();
    }

    public function test_progress_negatif_ditolak(): void
    {
        $m = $this->currentNative();
        $this->actingAs($this->user('pegawai'))->post(route('library.progress.update', $m), ['progress_percent' => -1])->assertSessionHasErrors('progress_percent');
    }

    public function test_progress_lebih_100_ditolak(): void
    {
        $m = $this->currentNative();
        $this->actingAs($this->user('pegawai'))->post(route('library.progress.update', $m), ['progress_percent' => 101])->assertSessionHasErrors('progress_percent');
    }

    public function test_progress_string_ditolak(): void
    {
        $m = $this->currentNative();
        $this->actingAs($this->user('pegawai'))->post(route('library.progress.update', $m), ['progress_percent' => 'abc'])->assertSessionHasErrors('progress_percent');
    }

    public function test_user_id_dari_request_diabaikan(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $other = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 55, 'user_id' => $other->id])->assertOk();
        $this->assertDatabaseHas('reading_progress', ['user_id' => $u->id, 'progress_percent' => 55]);
        $this->assertDatabaseMissing('reading_progress', ['user_id' => $other->id]);
    }

    public function test_material_dari_route_binding(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post('/perpustakaan/999999/progress', ['progress_percent' => 30])->assertNotFound();
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 30, 'learning_material_id' => 999999])->assertOk();
        $this->assertDatabaseHas('reading_progress', ['learning_material_id' => $m->id]);
    }

    public function test_started_at_diisi_pertama(): void
    {
        Carbon::setTestNow($now = Carbon::parse('2026-10-08 10:00:00'));
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 10])->assertOk();
        $p = ReadingProgress::first();
        $this->assertEquals($now->timestamp, $p->started_at->timestamp);
        Carbon::setTestNow($later = Carbon::parse('2026-10-08 11:00:00'));
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 40])->assertOk();
        $p->refresh();
        $this->assertEquals($now->timestamp, $p->started_at->timestamp);
        Carbon::setTestNow();
    }

    public function test_last_read_at_diperbarui(): void
    {
        Carbon::setTestNow($now = Carbon::parse('2026-10-08 10:00:00'));
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 15])->assertOk();
        $first = ReadingProgress::first()->last_read_at->timestamp;
        Carbon::setTestNow($later = Carbon::parse('2026-10-08 12:00:00'));
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 30])->assertOk();
        $this->assertSame($later->timestamp, ReadingProgress::first()->last_read_at->timestamp);
        $this->assertNotEquals($first, ReadingProgress::first()->last_read_at->timestamp);
        Carbon::setTestNow();
    }

    public function test_completed_at_null_kurang_100(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 70])->assertOk();
        $this->assertNull(ReadingProgress::first()->completed_at);
    }

    public function test_completed_at_terisi_100(): void
    {
        Carbon::setTestNow($now = Carbon::parse('2026-10-08 10:00:00'));
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 100])->assertOk();
        $this->assertEquals($now->timestamp, ReadingProgress::first()->completed_at->timestamp);
        Carbon::setTestNow();
    }

    public function test_progress_tidak_mundur(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 80])->assertOk();
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 30])->assertOk()->assertJson(['progress_percent' => 80]);
        $this->assertSame(80, ReadingProgress::first()->progress_percent);
    }

    public function test_progress_100_tetap_100(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 100])->assertOk();
        $firstCompleted = ReadingProgress::first()->completed_at;
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 40])->assertJson(['progress_percent' => 100]);
        $p = ReadingProgress::first();
        $this->assertSame(100, $p->progress_percent);
        $this->assertEquals($firstCompleted->timestamp, $p->completed_at->timestamp);
    }

    public function test_repeated_update_tidak_duplicate(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 20 + $i * 10])->assertOk();
        }
        $this->assertSame(1, ReadingProgress::count());
    }

    public function test_native_reader_menampilkan_progress_existing(): void
    {
        $m = $this->currentNative(['title' => 'Progress Reader Check Native']);
        $u = $this->user('pegawai');
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 42, 'started_at' => now(), 'last_read_at' => now()]);
        $resp = $this->actingAs($u)->get(route('library.read', $m));
        $resp->assertOk();
        $this->assertStringContainsString('42%', $resp->getContent());
    }

    public function test_index_menampilkan_progress(): void
    {
        $m = $this->currentNative(['title' => 'Index Progress Check']);
        $u = $this->user('pegawai');
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 55]);
        $resp = $this->actingAs($u)->get(route('library.index'));
        $resp->assertOk();
        $this->assertStringContainsString('55%', $resp->getContent());
    }

    // PDF
    public function test_pdf_dapat_ditandai_selesai(): void
    {
        Storage::fake('public');
        $m = $this->currentPdf();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 100])->assertOk()->assertJson(['completed' => true]);
        $this->assertDatabaseHas('reading_progress', ['user_id' => $u->id, 'progress_percent' => 100]);
    }

    public function test_pdf_mark_complete_completed_at(): void
    {
        Storage::fake('public');
        $m = $this->currentPdf();
        $u = $this->user('pegawai');
        Carbon::setTestNow($now = now());
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 100])->assertOk();
        $this->assertEquals($now->timestamp, ReadingProgress::first()->completed_at->timestamp);
        Carbon::setTestNow();
    }

    public function test_pdf_tidak_otomatis_selesai_hanya_dibuka(): void
    {
        Storage::fake('public');
        $m = $this->currentPdf();
        $u = $this->user('pegawai');
        $this->actingAs($u)->get(route('library.read', $m))->assertOk();
        $this->assertSame(0, ReadingProgress::count());
    }

    public function test_pdf_completion_butuh_authorization(): void
    {
        Storage::fake('public');
        $m = $this->currentPdf();
        $this->post(route('library.progress.update', $m), ['progress_percent' => 100])->assertRedirect(route('login'));
        $u = $this->user('pegawai');
        [$l, $c] = $this->lc();
        $draft = LearningMaterial::factory()->custom()->pdf()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value]);
        Storage::disk('public')->put($draft->file_path, '%PDF');
        $this->actingAs($u)->post(route('library.progress.update', $draft), ['progress_percent' => 100])->assertForbidden();
    }

    public function test_tidak_dapat_selesaikan_pdf_tidak_bisa_dibaca(): void
    {
        Storage::fake('public');
        $u = $this->user('pegawai');
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->pdf()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        Storage::disk('public')->put($m->file_path, '%PDF');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 100])->assertForbidden();
    }

    // LIFECYCLE
    public function test_unpublished_tidak_dapat_progress_consumer(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value]);
        $this->actingAs($this->user('pegawai'))->post(route('library.progress.update', $m), ['progress_percent' => 30])->assertForbidden();
        $this->actingAs($this->user('admin'))->post(route('library.progress.update', $m), ['progress_percent' => 30])->assertOk();
    }

    public function test_expired_tidak_dapat_progress(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'material_type' => 'native', 'content' => 'isi',
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value, 'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
        $m->publish();
        DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => LearningValidityStatus::Expired->value]);
        $this->actingAs($this->user('pegawai'))->post(route('library.progress.update', $m), ['progress_percent' => 50])->assertForbidden();
    }

    public function test_revoked_tidak_dapat_progress(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'material_type' => 'native', 'content' => 'isi',
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value, 'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
        $m->publish();
        DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => LearningValidityStatus::Revoked->value]);
        $this->actingAs($this->user('pegawai'))->post(route('library.progress.update', $m), ['progress_percent' => 50])->assertForbidden();
    }

    public function test_superseded_tidak_dapat_progress(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'material_type' => 'native', 'content' => 'isi',
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value, 'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
        $m->publish();
        DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => LearningValidityStatus::Superseded->value]);
        $this->actingAs($this->user('pegawai'))->post(route('library.progress.update', $m), ['progress_percent' => 50])->assertForbidden();
    }

    public function test_manager_tetap_policy(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value]);
        foreach (['admin', 'admin_library', 'pemilik'] as $r) {
            $this->actingAs($this->user($r))->post(route('library.progress.update', $m), ['progress_percent' => 25])->assertOk();
            ReadingProgress::query()->delete();
        }
    }

    public function test_delete_material_tidak_orphan(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 40]);
        $m->delete();
        $this->assertDatabaseMissing('reading_progress', ['learning_material_id' => $m->id]);
    }

    public function test_progress_tidak_hilang_ketika_unpublished(): void
    {
        $m = $this->currentNative();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 60])->assertOk();
        $m->unpublish();
        $this->assertDatabaseHas('reading_progress', ['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 60]);
    }
}
