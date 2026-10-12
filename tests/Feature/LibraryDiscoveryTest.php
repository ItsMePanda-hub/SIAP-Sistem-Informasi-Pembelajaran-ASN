<?php

namespace Tests\Feature;

use App\Enums\LearningPublicationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\LibraryBookmark;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryDiscoveryTest extends TestCase
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

    private function currentCustom(array $over = []): LearningMaterial
    {
        [$l, $c] = $this->lc();
        return LearningMaterial::factory()->custom()->native()->create(array_merge([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(),
        ], $over));
    }

    public function test_search_by_title(): void
    {
        $m1 = $this->currentCustom(['title' => 'Judul Alpha Unik Search']);
        $m2 = $this->currentCustom(['title' => 'Judul Beta Lain']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => 'Alpha Unik']));
        $resp->assertOk();
        $this->assertStringContainsString('Judul Alpha Unik Search', $resp->getContent());
        $this->assertStringNotContainsString('Judul Beta Lain', $resp->getContent());
    }

    public function test_search_by_description(): void
    {
        $m1 = $this->currentCustom(['title' => 'Desc Test A', 'description' => 'deskripsi khusus omega']);
        $m2 = $this->currentCustom(['title' => 'Desc Test B', 'description' => 'deskripsi lain']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => 'omega']));
        $resp->assertOk();
        $this->assertStringContainsString('Desc Test A', $resp->getContent());
        $this->assertStringNotContainsString('Desc Test B', $resp->getContent());
    }

    public function test_search_empty_shows_all(): void
    {
        $m1 = $this->currentCustom(['title' => 'EmptyQ A']);
        $m2 = $this->currentCustom(['title' => 'EmptyQ B']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => '']));
        $resp->assertOk();
        $this->assertStringContainsString('EmptyQ A', $resp->getContent());
        $this->assertStringContainsString('EmptyQ B', $resp->getContent());
        $resp2 = $this->actingAs($u)->get(route('library.index', ['q' => '   ']));
        $resp2->assertOk();
        $this->assertStringContainsString('EmptyQ A', $resp2->getContent());
    }

    public function test_search_trimmed_spaces(): void
    {
        $m = $this->currentCustom(['title' => 'Trim Space Judul']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => '  Trim Space  ']));
        $resp->assertOk();
        $this->assertStringContainsString('Trim Space Judul', $resp->getContent());
    }

    public function test_search_no_results_empty_state(): void
    {
        $this->currentCustom(['title' => 'Existing Mat']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => 'tidakadaXYZ123']));
        $resp->assertOk();
        $this->assertStringContainsString('Belum ada materi', $resp->getContent());
        $this->assertStringNotContainsString('Existing Mat', $resp->getContent());
    }

    public function test_search_does_not_leak_non_current(): void
    {
        [$l, $c] = $this->lc();
        $pub = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Published Visible']);
        $draft = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value, 'title' => 'Draft Hidden']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => 'Hidden']));
        $resp->assertOk();
        $this->assertStringNotContainsString('Draft Hidden', $resp->getContent());
        $resp2 = $this->actingAs($u)->get(route('library.index'));
        $resp2->assertOk();
        $this->assertStringContainsString('Published Visible', $resp2->getContent());
        $this->assertStringNotContainsString('Draft Hidden', $resp2->getContent());
    }

    public function test_filter_category(): void
    {
        [$l1, $c1] = $this->lc();
        $c2 = LearningCategory::factory()->create();
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l1->id, 'learning_category_id' => $c1->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Kategori A Mat']);
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l1->id, 'learning_category_id' => $c2->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Kategori B Mat']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['learning_category_id' => $c1->id]));
        $resp->assertOk();
        $this->assertStringContainsString('Kategori A Mat', $resp->getContent());
        $this->assertStringNotContainsString('Kategori B Mat', $resp->getContent());
    }

    public function test_filter_level(): void
    {
        [$l1, $c1] = $this->lc();
        $l2 = LearningLevel::factory()->create();
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l1->id, 'learning_category_id' => $c1->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Level A Mat']);
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l2->id, 'learning_category_id' => $c1->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Level B Mat']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['learning_level_id' => $l1->id]));
        $resp->assertOk();
        $this->assertStringContainsString('Level A Mat', $resp->getContent());
        $this->assertStringNotContainsString('Level B Mat', $resp->getContent());
    }

    public function test_filter_native(): void
    {
        [$l, $c] = $this->lc();
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Native Filter Mat']);
        LearningMaterial::factory()->custom()->pdf()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Pdf Filter Mat']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['material_type' => 'native']));
        $resp->assertOk();
        $this->assertStringContainsString('Native Filter Mat', $resp->getContent());
        $this->assertStringNotContainsString('Pdf Filter Mat', $resp->getContent());
    }

    public function test_filter_pdf(): void
    {
        [$l, $c] = $this->lc();
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Native Filter Mat2']);
        LearningMaterial::factory()->custom()->pdf()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Pdf Filter Mat2']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['material_type' => 'pdf']));
        $resp->assertOk();
        $this->assertStringContainsString('Pdf Filter Mat2', $resp->getContent());
        $this->assertStringNotContainsString('Native Filter Mat2', $resp->getContent());
    }

    public function test_filter_core_custom_as_consumer(): void
    {
        [$l, $c] = $this->lc();
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'scope' => 'custom', 'title' => 'Custom Scope Mat']);
        LearningMaterial::factory()->core()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value, 'title' => 'Core Scope Mat']);
        $u = $this->user('pegawai');
        $respCustom = $this->actingAs($u)->get(route('library.index', ['scope' => 'custom']));
        $respCustom->assertOk();
        $this->assertStringContainsString('Custom Scope Mat', $respCustom->getContent());
        $respCore = $this->actingAs($u)->get(route('library.index', ['scope' => 'core']));
        $respCore->assertOk();
        $this->assertStringNotContainsString('Custom Scope Mat', $respCore->getContent());
    }

    public function test_combination_search_and_filters(): void
    {
        [$l1, $c1] = $this->lc();
        $l2 = LearningLevel::factory()->create();
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l1->id, 'learning_category_id' => $c1->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Kombinasi Alpha Target', 'description' => 'match']);
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l2->id, 'learning_category_id' => $c1->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Kombinasi Alpha OtherLevel', 'description' => 'match']);
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l1->id, 'learning_category_id' => $c1->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Kombinasi Beta UniqueXYZ', 'description' => 'other']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => 'Kombinasi Alpha', 'learning_level_id' => $l1->id]));
        $resp->assertOk();
        $html = $resp->getContent();
        $this->assertStringContainsString('Kombinasi Alpha Target', $html);
        $this->assertStringNotContainsString('Kombinasi Alpha OtherLevel', $html);
        $this->assertStringNotContainsString('Kombinasi Beta UniqueXYZ', $html);
    }

    public function test_filter_invalid_handled_safely(): void
    {
        $m = $this->currentCustom(['title' => 'Invalid Filter Mat']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['material_type' => 'invalid', 'scope' => 'bad', 'learning_level_id' => 'notnum', 'publication_status' => 'bad', 'verification_status' => 'bad', 'validity_status' => 'bad', 'progress_status' => 'bad']));
        $resp->assertOk();
        $this->assertStringContainsString('Invalid Filter Mat', $resp->getContent());
        $resp2 = $this->actingAs($u)->get(route('library.index', ['learning_category_id' => '999999']));
        $resp2->assertOk();
        $this->assertStringNotContainsString('Invalid Filter Mat', $resp2->getContent());
    }

    public function test_sort_judul_asc(): void
    {
        $u = $this->user('pegawai');
        $this->currentCustom(['title' => 'Zebra Mat']);
        $this->currentCustom(['title' => 'Apple Mat']);
        $this->currentCustom(['title' => 'Mango Mat']);
        $resp = $this->actingAs($u)->get(route('library.index', ['sort' => 'judul_asc']));
        $resp->assertOk();
        $html = $resp->getContent();
        $posApple = strpos($html, 'Apple Mat');
        $posMango = strpos($html, 'Mango Mat');
        $posZebra = strpos($html, 'Zebra Mat');
        $this->assertNotFalse($posApple);
        $this->assertNotFalse($posMango);
        $this->assertNotFalse($posZebra);
        $this->assertTrue($posApple < $posMango && $posMango < $posZebra);
    }

    public function test_sort_judul_desc(): void
    {
        $u = $this->user('pegawai');
        $this->currentCustom(['title' => 'Apple Sort']);
        $this->currentCustom(['title' => 'Zebra Sort']);
        $this->currentCustom(['title' => 'Mango Sort']);
        $resp = $this->actingAs($u)->get(route('library.index', ['sort' => 'judul_desc']));
        $resp->assertOk();
        $html = $resp->getContent();
        $posZebra = strpos($html, 'Zebra Sort');
        $posMango = strpos($html, 'Mango Sort');
        $posApple = strpos($html, 'Apple Sort');
        $this->assertTrue($posZebra < $posMango && $posMango < $posApple);
    }

    public function test_sort_terbaru_default(): void
    {
        $u = $this->user('pegawai');
        $old = $this->currentCustom(['title' => 'Old Mat', 'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)]);
        $new = $this->currentCustom(['title' => 'New Mat']);
        $resp = $this->actingAs($u)->get(route('library.index'));
        $resp->assertOk();
        $html = $resp->getContent();
        $posNew = strpos($html, 'New Mat');
        $posOld = strpos($html, 'Old Mat');
        $this->assertTrue($posNew < $posOld);
    }

    public function test_sort_invalid_not_sql_injection(): void
    {
        $u = $this->user('pegawai');
        $this->currentCustom(['title' => 'Inject Mat']);
        $resp = $this->actingAs($u)->get(route('library.index', ['sort' => "title; DROP TABLE learning_materials; --"]));
        $resp->assertOk();
        $this->assertStringContainsString('Inject Mat', $resp->getContent());
        $this->assertDatabaseHas('learning_materials', ['title' => 'Inject Mat']);
        $resp2 = $this->actingAs($u)->get(route('library.index', ['sort' => "judul_asc' OR '1'='1"]));
        $resp2->assertOk();
    }

    public function test_pagination_retains_params(): void
    {
        $u = $this->user('pegawai');
        [$l, $c] = $this->lc();
        for ($i = 0; $i < 13; $i++) {
            LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => "Paginate Retain {$i} Alpha"]);
        }
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => 'Alpha', 'sort' => 'judul_asc']));
        $resp->assertOk();
        $html = strtolower($resp->getContent());
        $this->assertStringContainsString('pagination', $html);
        $this->assertStringContainsString('q=alpha', $html);
        $this->assertStringContainsString('sort=judul_asc', $html);
    }

    public function test_level_category_no_n_plus_one(): void
    {
        $u = $this->user('pegawai');
        $this->currentCustom(['title' => 'NPlus Check A']);
        $this->currentCustom(['title' => 'NPlus Check B']);
        DB::enableQueryLog();
        $this->actingAs($u)->get(route('library.index'))->assertOk();
        $queries = DB::getQueryLog();
        $materialQueries = array_filter($queries, fn ($q) => str_contains($q['query'] ?? '', 'learning_materials'));
        $this->assertLessThanOrEqual(3, count($materialQueries), 'N+1 material query detected');
        $levelQueries = array_filter($queries, fn ($q) => str_contains($q['query'] ?? '', 'learning_levels') || str_contains($q['query'] ?? '', 'learning_categories'));
        $this->assertLessThanOrEqual(6, count($levelQueries), 'N+1 level/category query detected');
        $progressQueries = array_filter($queries, fn ($q) => str_contains($q['query'] ?? '', 'reading_progress'));
        $this->assertLessThanOrEqual(2, count($progressQueries), 'N+1 progress query detected');
        $bookmarkQueries = array_filter($queries, fn ($q) => str_contains($q['query'] ?? '', 'library_bookmarks'));
        $this->assertLessThanOrEqual(1, count($bookmarkQueries), 'N+1 bookmark query detected');
    }

    public function test_bookmark_status_correct(): void
    {
        $u = $this->user('pegawai');
        $m1 = $this->currentCustom(['title' => 'Bookmark Discovery A']);
        $m2 = $this->currentCustom(['title' => 'Bookmark Discovery B']);
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m1->id]);
        $resp = $this->actingAs($u)->get(route('library.index'));
        $resp->assertOk();
        $html = $resp->getContent();
        $this->assertStringContainsString('Bookmark Discovery A', $html);
        $this->assertStringContainsString('Bookmark Discovery B', $html);
        $this->assertStringContainsString('★ Tersimpan', $html);
        $this->assertStringContainsString('☆ Simpan', $html);
    }

    public function test_progress_status_correct(): void
    {
        $u = $this->user('pegawai');
        $m1 = $this->currentCustom(['title' => 'Progress Discovery A']);
        $m2 = $this->currentCustom(['title' => 'Progress Discovery B']);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m1->id, 'progress_percent' => 100]);
        $resp = $this->actingAs($u)->get(route('library.index'));
        $resp->assertOk();
        $html = $resp->getContent();
        $this->assertStringContainsString('Selesai', $html);
        $this->assertStringContainsString('Belum mulai', $html);
    }

    public function test_link_baca_authorization(): void
    {
        [$l, $c] = $this->lc();
        $pub = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Baca Auth Pub']);
        $draft = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value, 'title' => 'Baca Auth Draft']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index'));
        $resp->assertOk();
        $html = $resp->getContent();
        $this->assertStringContainsString(route('library.read', $pub), $html);
        $this->assertStringNotContainsString(route('library.read', $draft), $html);
    }

    public function test_manager_sees_draft_via_search(): void
    {
        [$l, $c] = $this->lc();
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value, 'title' => 'Manager Draft Visible']);
        $pegawai = $this->user('pegawai');
        $admin = $this->user('admin');
        $this->actingAs($pegawai)->get(route('library.index', ['q' => 'Manager Draft']))->assertDontSee('Manager Draft Visible');
        $this->actingAs($admin)->get(route('library.index', ['q' => 'Manager Draft']))->assertSee('Manager Draft Visible');
    }

    public function test_guest_redirect(): void
    {
        $this->get(route('library.index'))->assertRedirect(route('login'));
    }

    public function test_filter_progress_status(): void
    {
        $u = $this->user('pegawai');
        $mBelum = $this->currentCustom(['title' => 'Prog Filter Belum']);
        $mSedang = $this->currentCustom(['title' => 'Prog Filter Sedang']);
        $mSelesai = $this->currentCustom(['title' => 'Prog Filter Selesai']);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $mSedang->id, 'progress_percent' => 50]);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $mSelesai->id, 'progress_percent' => 100]);
        $respBelum = $this->actingAs($u)->get(route('library.index', ['progress_status' => 'belum_mulai']));
        $respBelum->assertOk();
        $this->assertStringContainsString('Prog Filter Belum', $respBelum->getContent());
        $this->assertStringNotContainsString('Prog Filter Sedang', $respBelum->getContent());
        $respSedang = $this->actingAs($u)->get(route('library.index', ['progress_status' => 'sedang_membaca']));
        $respSedang->assertOk();
        $this->assertStringContainsString('Prog Filter Sedang', $respSedang->getContent());
        $this->assertStringNotContainsString('Prog Filter Selesai', $respSedang->getContent());
        $respSelesai = $this->actingAs($u)->get(route('library.index', ['progress_status' => 'selesai']));
        $respSelesai->assertOk();
        $this->assertStringContainsString('Prog Filter Selesai', $respSelesai->getContent());
        $this->assertStringNotContainsString('Prog Filter Belum', $respSelesai->getContent());
    }

    public function test_result_count_and_active_filters_displayed(): void
    {
        $this->currentCustom(['title' => 'Count Filter Mat']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => 'Count Filter']));
        $resp->assertOk();
        $html = $resp->getContent();
        $this->assertStringContainsString('Menampilkan', $html);
        $this->assertStringContainsString('Filter aktif', $html);
    }

    public function test_search_percent_is_literal_not_wildcard(): void
    {
        $literal = $this->currentCustom(['title' => 'Siap 100% Lulus Ujian']);
        $other = $this->currentCustom(['title' => 'Siap Lulus Ujian Biasa']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => '100%']));
        $resp->assertOk();
        $this->assertStringContainsString('Siap 100% Lulus Ujian', $resp->getContent());
        $this->assertStringNotContainsString('Siap Lulus Ujian Biasa', $resp->getContent());
    }

    public function test_search_underscore_is_literal_not_wildcard(): void
    {
        $literal = $this->currentCustom(['title' => 'Modul_A Khusus Literal']);
        $other = $this->currentCustom(['title' => 'Modul B Biasa']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index', ['q' => 'Modul_A']));
        $resp->assertOk();
        $this->assertStringContainsString('Modul_A Khusus Literal', $resp->getContent());
        $this->assertStringNotContainsString('Modul B Biasa', $resp->getContent());
    }

    public function test_search_encoded_percent_is_literal(): void
    {
        $this->currentCustom(['title' => 'Siap 100% Lulus Ujian']);
        $this->currentCustom(['title' => 'Siap Lulus Ujian Biasa']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get('/perpustakaan?q=100%25');
        $resp->assertOk();
        $this->assertStringContainsString('Siap 100% Lulus Ujian', $resp->getContent());
        $this->assertStringNotContainsString('Siap Lulus Ujian Biasa', $resp->getContent());
    }

    public function test_search_normal_term_and_empty_result(): void
    {
        $this->currentCustom(['title' => 'Siap Normal Term Unik']);
        $u = $this->user('pegawai');
        $hit = $this->actingAs($u)->get(route('library.index', ['q' => 'Siap']));
        $hit->assertOk();
        $this->assertStringContainsString('Siap Normal Term Unik', $hit->getContent());
        $miss = $this->actingAs($u)->get(route('library.index', ['q' => '100%_ZZZ_TIDAK_ADA']));
        $miss->assertOk();
        $this->assertStringContainsString('Belum ada materi.', $miss->getContent());
    }
}
