<?php

namespace Tests\Feature;

use App\Enums\LearningPublicationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialSource;
use App\Models\LibraryBookmark;
use App\Models\ReadingProgress;
use App\Models\User;
use App\Services\LibraryRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryRecommendationTest extends TestCase
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

    private function coreCurrent(): LearningMaterial
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $l->id, 'learning_category_id' => $c->id,
            'material_type' => 'native', 'content' => 'isi',
            'verification_status' => 'verified',
            'validity_status' => 'active', 'publication_status' => 'draft',
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
        $m->publish();
        return $m->fresh();
    }

    private function service(): LibraryRecommendationService
    {
        return new LibraryRecommendationService();
    }

    public function test_service_returns_recommendations(): void
    {
        $u = $this->user('pegawai');
        $this->currentCustom(['title' => 'Rec A']);
        $recs = $this->service()->getRecommendations($u);
        $this->assertNotEmpty($recs);
        $this->assertStringContainsString('Rec A', $recs->first()['material']->title);
    }

    public function test_limited_max_6(): void
    {
        $u = $this->user('pegawai');
        [$l, $c] = $this->lc();
        for ($i = 0; $i < 10; $i++) {
            LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => "Limit Mat {$i}"]);
        }
        $recs = $this->service()->getRecommendations($u);
        $this->assertLessThanOrEqual(6, $recs->count());
    }

    public function test_no_duplicates(): void
    {
        $u = $this->user('pegawai');
        [$l, $c] = $this->lc();
        for ($i = 0; $i < 5; $i++) {
            LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => "Dedup Mat {$i}"]);
        }
        $recs = $this->service()->getRecommendations($u);
        $ids = $recs->map(fn ($r) => $r['material']->id)->all();
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_unfinished_prioritized(): void
    {
        $u = $this->user('pegawai');
        $mContinue = $this->currentCustom(['title' => 'Continue Mat']);
        $mNew = $this->currentCustom(['title' => 'Newbie Mat']);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $mContinue->id, 'progress_percent' => 40]);
        $recs = $this->service()->getRecommendations($u);
        $this->assertSame($mContinue->id, $recs->first()['material']->id);
        $this->assertStringContainsString('Lanjutkan', $recs->first()['reason']);
    }

    public function test_completed_not_dominant(): void
    {
        $u = $this->user('pegawai');
        $mDone = $this->currentCustom(['title' => 'Done Mat']);
        $mTodo = $this->currentCustom(['title' => 'Todo Mat']);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $mDone->id, 'progress_percent' => 100]);
        $recs = $this->service()->getRecommendations($u);
        $ids = $recs->map(fn ($r) => $r['material']->id)->all();
        $this->assertContains($mTodo->id, $ids);
        $this->assertNotContains($mDone->id, $ids);
    }

    public function test_recommendation_does_not_modify_progress(): void
    {
        $u = $this->user('pegawai');
        $m = $this->currentCustom(['title' => 'NoMod Prog']);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 55]);
        $before = ReadingProgress::count();
        $beforeVal = ReadingProgress::first()->progress_percent;
        $this->service()->getRecommendations($u);
        $this->assertSame($before, ReadingProgress::count());
        $this->assertSame($beforeVal, ReadingProgress::first()->progress_percent);
    }

    public function test_recommendation_does_not_modify_bookmark(): void
    {
        $u = $this->user('pegawai');
        $m = $this->currentCustom(['title' => 'NoMod Bookmark']);
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $before = LibraryBookmark::count();
        $this->service()->getRecommendations($u);
        $this->assertSame($before, LibraryBookmark::count());
    }

    public function test_category_signal_used_when_data(): void
    {
        $u = $this->user('pegawai');
        [$l, $c1] = $this->lc();
        $c2 = LearningCategory::factory()->create();
        $studied = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c1->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Studied Cat']);
        $sameCat = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c1->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Same Cat Candidate']);
        $otherCat = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c2->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Other Cat Candidate']);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $studied->id, 'progress_percent' => 100]);
        $recs = $this->service()->getRecommendations($u);
        $first = $recs->first();
        $this->assertSame($sameCat->id, $first['material']->id);
        $this->assertStringContainsString('kategori', strtolower($first['reason']));
    }

    public function test_ranking_deterministic(): void
    {
        $u = $this->user('pegawai');
        [$l, $c] = $this->lc();
        for ($i = 0; $i < 4; $i++) {
            LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => "Det Mat {$i}"]);
        }
        $first = $this->service()->getRecommendations($u)->map(fn ($r) => $r['material']->id)->all();
        $second = $this->service()->getRecommendations($u)->map(fn ($r) => $r['material']->id)->all();
        $this->assertSame($first, $second);
    }

    public function test_reason_matches_rule(): void
    {
        $u = $this->user('pegawai');
        $m = $this->currentCustom(['title' => 'Reason Check']);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 30]);
        $recs = $this->service()->getRecommendations($u);
        $first = $recs->first();
        $this->assertSame($m->id, $first['material']->id);
        $this->assertSame('Lanjutkan materi yang belum selesai.', $first['reason']);
    }

    public function test_unpublished_not_leaked(): void
    {
        [$l, $c] = $this->lc();
        LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value, 'title' => 'Draft Rec Hidden']);
        $this->currentCustom(['title' => 'Visible Rec']);
        $u = $this->user('pegawai');
        $recs = $this->service()->getRecommendations($u);
        $titles = $recs->map(fn ($r) => $r['material']->title)->all();
        $this->assertNotContains('Draft Rec Hidden', $titles);
    }

    public function test_core_unverified_not_for_consumer(): void
    {
        [$l, $c] = $this->lc();
        LearningMaterial::factory()->core()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value, 'title' => 'Core Draft Hidden']);
        $this->currentCustom(['title' => 'Custom Visible']);
        $u = $this->user('pegawai');
        $recs = $this->service()->getRecommendations($u);
        $titles = $recs->map(fn ($r) => $r['material']->title)->all();
        $this->assertNotContains('Core Draft Hidden', $titles);
    }

    public function test_expired_revoked_superseded_not_leaked(): void
    {
        $m = $this->coreCurrent();
        DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => 'expired']);
        $this->currentCustom(['title' => 'Fallback Visible']);
        $u = $this->user('pegawai');
        $recs = $this->service()->getRecommendations($u);
        $ids = $recs->map(fn ($r) => $r['material']->id)->all();
        $this->assertNotContains($m->id, $ids);
    }

    public function test_custom_follows_lifecycle(): void
    {
        [$l, $c] = $this->lc();
        $draft = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value, 'title' => 'Custom Draft']);
        $pub = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now(), 'title' => 'Custom Pub']);
        $u = $this->user('pegawai');
        $recs = $this->service()->getRecommendations($u);
        $ids = $recs->map(fn ($r) => $r['material']->id)->all();
        $this->assertContains($pub->id, $ids);
        $this->assertNotContains($draft->id, $ids);
    }

    public function test_manager_sees_draft(): void
    {
        [$l, $c] = $this->lc();
        $draft = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value, 'title' => 'Manager Draft Rec']);
        $admin = $this->user('admin');
        $recs = $this->service()->getRecommendations($admin);
        $ids = $recs->map(fn ($r) => $r['material']->id)->all();
        $this->assertContains($draft->id, $ids);
    }

    public function test_rec_link_respects_policy(): void
    {
        [$l, $c] = $this->lc();
        $draft = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value, 'title' => 'Policy Link Draft']);
        $u = $this->user('pegawai');
        $this->actingAs($u)->get(route('library.read', $draft))->assertForbidden();
    }

    public function test_empty_state_when_no_candidate(): void
    {
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.index'));
        $resp->assertOk();
        $this->assertStringContainsString('Rekomendasi untuk Anda', $resp->getContent());
        $this->assertStringContainsString('Belum ada rekomendasi', $resp->getContent());
    }

    public function test_ui_shows_reason(): void
    {
        $u = $this->user('pegawai');
        $m = $this->currentCustom(['title' => 'UI Reason Mat']);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 40]);
        $resp = $this->actingAs($u)->get(route('library.index'));
        $resp->assertOk();
        $html = $resp->getContent();
        $this->assertStringContainsString('Rekomendasi untuk Anda', $html);
        $this->assertStringContainsString('Lanjutkan materi yang belum selesai.', $html);
        $this->assertStringContainsString('UI Reason Mat', $html);
    }

    public function test_ui_shows_progress_correctly(): void
    {
        Storage::fake('public');
        $u = $this->user('pegawai');
        $m = $this->currentCustom(['title' => 'UI Progress Mat']);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 40]);
        $resp = $this->actingAs($u)->get(route('library.index'));
        $resp->assertOk();
        $this->assertStringContainsString('40%', $resp->getContent());
    }

    public function test_existing_library_features_still_work(): void
    {
        Storage::fake('public');
        $u = $this->user('pegawai');
        $m = $this->currentCustom(['title' => 'Regression Mat']);
        $this->actingAs($u)->get(route('library.index', ['q' => 'Regression']))->assertSee('Regression Mat');
        $this->actingAs($u)->post(route('library.bookmark.store', $m))->assertRedirect();
        $this->assertDatabaseHas('library_bookmarks', ['user_id' => $u->id]);
        $this->actingAs($u)->get(route('library.read', $m))->assertOk();
    }
}
