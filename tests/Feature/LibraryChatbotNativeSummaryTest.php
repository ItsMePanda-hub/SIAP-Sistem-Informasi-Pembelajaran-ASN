<?php

namespace Tests\Feature;

use App\Enums\LearningPublicationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\ReadingProgress;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class LibraryChatbotNativeSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function lc(): array { return [LearningLevel::factory()->create(), LearningCategory::factory()->create()]; }
    private function user(string $role = 'pegawai'): User { return User::factory()->create(['role' => $role]); }

    private function nativeCustomPublished(string $title, string $content): LearningMaterial
    {
        [$l, $c] = $this->lc();
        return LearningMaterial::factory()->custom()->native()->create([
            'title' => $title, 'content' => $content,
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ]);
    }

    private function coreNativePublished(string $title, string $content): LearningMaterial
    {
        [$l, $c] = $this->lc();
        $reviewer = $this->user('admin_library');
        $uploader = $this->user('admin_library');
        $m = LearningMaterial::factory()->core()->create([
            'title' => $title, 'content' => $content, 'material_type' => 'native',
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'uploaded_by' => $uploader->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'verification_status' => \App\Enums\LearningVerificationStatus::Verified->value,
            'validity_status' => \App\Enums\LearningValidityStatus::Active->value,
        ]);
        \App\Models\LearningMaterialSource::factory()->create([
            'learning_material_id' => $m->id, 'source_name' => 'Sumber A', 'publisher' => 'Penerbit A',
            'license_type' => 'cc-by-4.0', 'license_url' => 'https://example.com/license',
            'source_url' => 'https://example.com/source',
            'rights_status' => 'approved', 'rights_reviewed_by' => $reviewer->id, 'rights_reviewed_at' => now(),
        ]);
        $m->publish(); return $m->fresh();
    }

    private function pdfCustomPublished(string $title): LearningMaterial
    {
        Storage::fake('public');
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->pdf()->create([
            'title' => $title, 'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ]);
        Storage::disk('public')->put($m->file_path, '%PDF-rangkuman rahasia pdf tidak boleh bocor%');
        return $m->fresh();
    }

    private function linkOnlyCustomPublished(string $title): LearningMaterial
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->create([
            'title' => $title, 'material_type' => 'link_only', 'content' => null, 'file_path' => null,
            'file_original_name' => null, 'file_mime' => null, 'file_size' => null,
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ]);
        \App\Models\LearningMaterialSource::factory()->create([
            'learning_material_id' => $m->id,
            'source_name' => 'Sumber Link', 'publisher' => 'Publisher Link',
            'source_url' => 'https://example.com/external-rahasia-link',
            'license_type' => 'cc-by-4.0', 'license_url' => 'https://example.com/license',
            'rights_status' => 'approved', 'rights_reviewed_by' => User::factory()->create(['role' => 'admin_library'])->id, 'rights_reviewed_at' => now(),
        ]);
        return $m->fresh();
    }

    public function test_rangkuman_satu_materi_native_berhasil(): void
    {
        $u = $this->user('pegawai');
        $m = $this->nativeCustomPublished('Literasi Digital', 'Konten Literasi Digital: etika digital dan keamanan siber dasar.');
        $this->mock(GeminiService::class, function (MockInterface $mock) use ($m) {
            $mock->shouldReceive('ask')->once()->withArgs(function ($system, $ctx, $q) use ($m) {
                if (! isset($ctx['library_native'])) return false;
                $titles = collect($ctx['library_native']['materials'] ?? [])->pluck('title')->all();
                $hasContent = collect($ctx['library_native']['materials'] ?? [])->pluck('content')->implode(' ');
                $noPdf = ! str_contains(json_encode($ctx), 'rahasiapdf') && ! str_contains(json_encode($ctx), 'external-rahasia');
                return in_array($m->title, $titles, true) && str_contains($hasContent, 'etika digital') && $noPdf
                    && str_contains($system, 'LIBRARY NATIVE');
            })->andReturn('Rangkuman Literasi Digital: ...');
        });
        $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Rangkum materi Literasi Digital.'])->assertOk()->assertJson(['reply' => 'Rangkuman Literasi Digital: ...']);
    }

    public function test_rangkuman_lintas_materi_hanya_native_diizinkan(): void
    {
        $u = $this->user('pegawai');
        $n1 = $this->nativeCustomPublished('Komunikasi Efektif', 'Isi Komunikasi Efektif: teknik mendengarkan aktif.');
        $n2 = $this->nativeCustomPublished('Pelayanan Publik', 'Isi Pelayanan Publik: standar pelayanan prima.');
        $this->pdfCustomPublished('Dokumen PDF Rahasia');
        $this->linkOnlyCustomPublished('Tautan Eksternal Rahasia');
        $this->mock(GeminiService::class, function (MockInterface $mock) use ($n1, $n2) {
            $mock->shouldReceive('ask')->once()->withArgs(function ($s, $ctx, $q) use ($n1, $n2) {
                $lib = $ctx['library_native'] ?? null; if (! $lib) return false;
                $titles = collect($lib['materials'])->pluck('title')->all();
                $json = json_encode($ctx);
                $noLeak = ! str_contains($json, 'rahasiapdf') && ! str_contains($json, 'external-rahasia') && ! str_contains($json, 'Rahasia');
                $hasBoth = in_array($n1->title, $titles, true) && in_array($n2->title, $titles, true);
                $rulesOk = str_contains(json_encode($lib), 'bedakan sumber');
                return $hasBoth && $noLeak && $rulesOk && str_contains($s, 'bedakan sumber');
            })->andReturn('Rangkuman lintas ...');
        });
        $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Bandingkan poin utama dari beberapa materi Native yang boleh saya akses.'])->assertOk();
    }

    public function test_pdf_dan_linkonly_tidak_pernah_masuk_konteks(): void
    {
        $u = $this->user('pegawai');
        $pdf = $this->pdfCustomPublished('Arsip PDF Penting');
        $link = $this->linkOnlyCustomPublished('Referensi LinkOnly Penting');
        $native = $this->nativeCustomPublished('Keamanan Informasi', 'Isi Keamanan Informasi: enkripsi dan kebijakan kata sandi.');
        $this->mock(GeminiService::class, function (MockInterface $mock) use ($native) {
            $mock->shouldReceive('ask')->once()->withArgs(function ($system, $ctx, $q) use ($native) {
                if (isset($ctx['library_native'])) {
                    $json = json_encode($ctx['library_native']);
                    if (str_contains($json, 'external-rahasia') || str_contains(strtolower($json), 'rahasiapdf')) return false;
                }
                $jsonAll = json_encode($ctx);
                return ! str_contains($jsonAll, 'external-rahasia') && ! str_contains($jsonAll, '%PDF-');
            })->andReturn('Jawaban native saja');
        });
        $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Jelaskan ulang bagian yang sulit dari Keamanan Informasi.'])->assertOk();
        $resp2 = $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Rangkum materi Arsip PDF Penting.'])->assertOk();
        $this->assertStringContainsString('bertipe PDF', $resp2->json('reply'));
    }

    public function test_materi_tidak_dapat_diakses_tidak_bocor(): void
    {
        $u = $this->user('pegawai');
        [$l, $c] = $this->lc();
        $draft = LearningMaterial::factory()->custom()->native()->create([
            'title' => 'Materi Draft Rahasia', 'content' => 'KONTEN RAHASIA DRAFT TIDAK BOLEH BOCOR',
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        $this->mock(GeminiService::class, function (MockInterface $mock) {});
        $resp = $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Rangkum materi Materi Draft Rahasia.']);
        $resp->assertOk();
        $reply = $resp->json('reply');
        $this->assertStringNotContainsString('KONTEN RAHASIA DRAFT', $reply);
        $this->assertStringNotContainsString('KONTEN RAHASIA DRAFT', json_encode($resp->json()));
        \Mockery::close();
        $this->mock(GeminiService::class, function (MockInterface $mock) { $mock->shouldReceive('ask')->andReturn('x'); });
        $resp2 = $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Rangkum materi Materi Draft Rahasia.']);
        $this->assertStringNotContainsString('KONTEN RAHASIA DRAFT', json_encode($resp2->json()));
    }

    public function test_materi_tidak_ditemukan_jawaban_keterbatasan_jelas(): void
    {
        $u = $this->user('pegawai');
        $this->mock(GeminiService::class, function (MockInterface $mock) { $mock->shouldNotReceive('ask'); });
        $resp = $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Rangkum materi Topik Tidak Ada XYZ123.']);
        $resp->assertOk();
        $this->assertMatchesRegularExpression('/tidak ditemukan|tidak tersedia|tidak dapat diakses/i', $resp->json('reply'));
    }

    public function test_tidak_mengarang_sop_jika_tidak_tercantum(): void
    {
        $u = $this->user('pegawai');
        $this->nativeCustomPublished('Etika ASN', 'Konten Etika ASN: integritas dan profesionalisme.');
        $this->mock(GeminiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('ask')->once()->withArgs(function ($system, $ctx, $q) {
                return isset($ctx['library_native']) && str_contains($system, 'tidak tercantum dalam materi');
            })->andReturn('Informasi tersebut tidak tercantum dalam materi.');
        });
        $resp = $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Apa SOP pengajuan cuti menurut materi Etika ASN?']);
        $resp->assertOk();
        $this->assertStringNotContainsString('SOP', $resp->json('reply') === 'Informasi tersebut tidak tercantum dalam materi.' ? '' : $resp->json('reply'));
    }

    public function test_progres_di_bawah_100_tidak_menghalangi_dan_tidak_otomatis(): void
    {
        $u = $this->user('pegawai');
        $m = $this->nativeCustomPublished('Literasi Digital', 'Isi Literasi Digital lengkap untuk dirangkum.');
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 30, 'started_at' => now(), 'last_read_at' => now()]);
        $this->mock(GeminiService::class, function (MockInterface $mock) use ($m) {
            $mock->shouldReceive('ask')->once()->withArgs(function ($s, $ctx, $q) use ($m) {
                $lib = $ctx['library_native'] ?? null; if (! $lib) return false;
                $prog = collect($lib['materials'])->firstWhere('title', $m->title)['progress_percent'] ?? null;
                return $prog === 30 && str_contains($s, 'progress_percent <100');
            })->andReturn('Rangkuman tetap diberikan walau progres 30%. Anda dapat melanjutkan membaca.');
        });
        $resp = $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Rangkum materi Literasi Digital.']);
        $resp->assertOk();
        $this->assertSame(30, ReadingProgress::where('user_id', $u->id)->where('learning_material_id', $m->id)->first()->progress_percent);
    }

    public function test_permintaan_rangkuman_tetap_berbasis_permintaan_bukan_otomatis(): void
    {
        $u = $this->user('pegawai');
        $m = $this->nativeCustomPublished('Pelayanan Publik', 'Isi Pelayanan Publik untuk rangkuman.');
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 100, 'started_at' => now(), 'last_read_at' => now(), 'completed_at' => now()]);
        $this->mock(GeminiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('ask')->once()->withArgs(function ($s, $ctx, $q) {
                return ! isset($ctx['library_native']) || empty($ctx['library_native']['materials']);
            })->andReturn('Halo, ada yang bisa dibantu seputar SIAP?');
        });
        $resp = $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Halo, ada info pengumuman terbaru?']);
        $resp->assertOk();
        $this->assertStringNotContainsString('Rangkuman', $resp->json('reply'));
        $this->assertSame(100, ReadingProgress::where('user_id', $u->id)->where('learning_material_id', $m->id)->first()->progress_percent);
    }

    public function test_chatbot_tidak_mengubah_progres_saat_rangkuman(): void
    {
        $u = $this->user('pegawai');
        $m = $this->nativeCustomPublished('Komunikasi Efektif', 'Isi Komunikasi Efektif untuk rangkuman.');
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 45, 'started_at' => now(), 'last_read_at' => now()]);
        $this->mock(GeminiService::class, function (MockInterface $mock) { $mock->shouldReceive('ask')->andReturn('Rangkuman ...'); });
        $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Rangkum materi Komunikasi Efektif.'])->assertOk();
        $this->assertSame(45, ReadingProgress::where('user_id', $u->id)->where('learning_material_id', $m->id)->first()->progress_percent);
        $this->assertSame(1, ReadingProgress::count());
    }

    public function test_fitur_chatbot_existing_tidak_rusak(): void
    {
        $u = $this->user('pegawai');
        $this->mock(GeminiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('ask')->once()->andReturn('Jawaban pengumuman');
        });
        $resp = $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Apa pengumuman terbaru untuk unit saya?']);
        $resp->assertOk()->assertJson(['reply' => 'Jawaban pengumuman']);
        $this->assertDatabaseHas('chatbot_logs', ['user_id' => $u->id, 'message' => 'Apa pengumuman terbaru untuk unit saya?']);
    }

    public function test_core_expired_tidak_masuk_rangkuman(): void
    {
        $u = $this->user('pegawai');
        $m = $this->coreNativePublished('Materi Core Aktif', 'Isi Core Aktif.');
        \Illuminate\Support\Facades\DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => \App\Enums\LearningValidityStatus::Expired->value]);
        $this->mock(GeminiService::class, function (MockInterface $mock) { $mock->shouldNotReceive('ask'); });
        $resp = $this->actingAs($u)->postJson('/chatbot/tanya', ['message' => 'Rangkum materi Materi Core Aktif.']);
        $resp->assertOk();
        $this->assertMatchesRegularExpression('/tidak ditemukan|tidak tersedia|tidak dapat diakses/i', $resp->json('reply'));
        $this->assertStringNotContainsString('Isi Core Aktif', $resp->json('reply'));
    }
}
