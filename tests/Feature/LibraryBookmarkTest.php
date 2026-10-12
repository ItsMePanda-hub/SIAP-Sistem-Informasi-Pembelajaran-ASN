<?php

namespace Tests\Feature;

use App\Enums\LearningPublicationStatus;
use App\Enums\LearningValidityStatus;
use App\Enums\LearningVerificationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialSource;
use App\Models\LibraryBookmark;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryBookmarkTest extends TestCase
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

    private function currentMaterial(array $over = []): LearningMaterial
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
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value, 'publication_status' => LearningPublicationStatus::Draft->value,
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id' => $m->id, 'source_name' => 'S', 'publisher' => 'P']);
        $m->publish();
        return $m->fresh();
    }

    // DATABASE
    public function test_model_exists_and_relations(): void
    {
        $u = $this->user('pegawai');
        $m = $this->currentMaterial();
        $bm = LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $this->assertTrue($bm->user->is($u));
        $this->assertTrue($bm->learningMaterial->is($m));
        $this->assertTrue($u->libraryBookmarks()->where('id', $bm->id)->exists());
        $this->assertTrue($m->libraryBookmarks()->where('id', $bm->id)->exists());
    }

    public function test_unique_user_material(): void
    {
        $u = $this->user('pegawai');
        $m = $this->currentMaterial();
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
    }

    public function test_cascade_user_delete(): void
    {
        $u = $this->user('pegawai');
        $m = $this->currentMaterial();
        $bm = LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $u->delete();
        $this->assertDatabaseMissing('library_bookmarks', ['id' => $bm->id]);
    }

    public function test_cascade_material_delete(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        $bm = LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $m->delete();
        $this->assertDatabaseMissing('library_bookmarks', ['id' => $bm->id]);
    }

    // STORE
    public function test_authenticated_can_bookmark_current(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.bookmark.store', $m))->assertRedirect();
        $this->assertDatabaseHas('library_bookmarks', ['user_id' => $u->id, 'learning_material_id' => $m->id]);
    }

    public function test_guest_cannot_bookmark(): void
    {
        $m = $this->currentMaterial();
        $this->post(route('library.bookmark.store', $m))->assertRedirect(route('login'));
    }

    public function test_duplicate_bookmark_no_duplicate_row(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.bookmark.store', $m))->assertRedirect();
        $this->actingAs($u)->post(route('library.bookmark.store', $m))->assertRedirect();
        $this->assertSame(1, LibraryBookmark::where('user_id', $u->id)->where('learning_material_id', $m->id)->count());
    }

    public function test_user_id_from_auth(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        $other = $this->user('pegawai');
        $this->actingAs($u)->post(route('library.bookmark.store', $m), ['user_id' => $other->id])->assertRedirect();
        $this->assertDatabaseHas('library_bookmarks', ['user_id' => $u->id]);
        $this->assertDatabaseMissing('library_bookmarks', ['user_id' => $other->id]);
    }

    public function test_material_from_route_binding(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        $this->actingAs($u)->post('/perpustakaan/999999/bookmark')->assertNotFound();
        $this->actingAs($u)->post(route('library.bookmark.store', $m), ['learning_material_id' => 999999])->assertRedirect();
        $this->assertDatabaseHas('library_bookmarks', ['learning_material_id' => $m->id]);
    }

    public function test_unauthorized_cannot_bookmark_non_current(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value]);
        $this->actingAs($this->user('pegawai'))->post(route('library.bookmark.store', $m))->assertForbidden();
        $this->actingAs($this->user('atasan'))->post(route('library.bookmark.store', $m))->assertForbidden();
    }

    public function test_manager_can_bookmark(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value]);
        foreach (['admin', 'admin_library', 'pemilik'] as $r) {
            $u = $this->user($r);
            $this->actingAs($u)->post(route('library.bookmark.store', $m))->assertRedirect();
            $this->assertDatabaseHas('library_bookmarks', ['user_id' => $u->id]);
        }
    }

    // DESTROY
    public function test_user_can_remove_own_bookmark(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $this->actingAs($u)->delete(route('library.bookmark.destroy', $m))->assertRedirect();
        $this->assertDatabaseMissing('library_bookmarks', ['user_id' => $u->id]);
    }

    public function test_removing_nonexistent_is_safe(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        $this->actingAs($u)->delete(route('library.bookmark.destroy', $m))->assertRedirect();
        $this->assertSame(0, LibraryBookmark::count());
    }

    public function test_cannot_remove_another_users_bookmark(): void
    {
        $m = $this->currentMaterial();
        $a = $this->user('pegawai');
        $b = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $a->id, 'learning_material_id' => $m->id]);
        $this->actingAs($b)->delete(route('library.bookmark.destroy', $m))->assertRedirect();
        $this->assertDatabaseHas('library_bookmarks', ['user_id' => $a->id]);
        $this->assertDatabaseMissing('library_bookmarks', ['user_id' => $b->id]);
    }

    public function test_unbookmark_does_not_remove_progress(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 40]);
        $this->actingAs($u)->delete(route('library.bookmark.destroy', $m))->assertRedirect();
        $this->assertDatabaseHas('reading_progress', ['user_id' => $u->id]);
        $this->assertDatabaseMissing('library_bookmarks', ['user_id' => $u->id]);
    }

    public function test_unbookmark_does_not_modify_material(): void
    {
        $m = $this->currentMaterial(['title' => 'Keep Title']);
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $this->actingAs($u)->delete(route('library.bookmark.destroy', $m))->assertRedirect();
        $this->assertDatabaseHas('learning_materials', ['id' => $m->id, 'title' => 'Keep Title']);
    }

    // LIFECYCLE
    public function test_unpublished_cannot_be_bookmarked_by_consumer(): void
    {
        [$l, $c] = $this->lc();
        $m = LearningMaterial::factory()->custom()->native()->create(['learning_level_id' => $l->id, 'learning_category_id' => $c->id, 'publication_status' => LearningPublicationStatus::Draft->value]);
        $this->actingAs($this->user('pegawai'))->post(route('library.bookmark.store', $m))->assertForbidden();
    }

    public function test_expired_cannot_be_bookmarked(): void
    {
        $m = $this->coreCurrent();
        DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => LearningValidityStatus::Expired->value]);
        $this->actingAs($this->user('pegawai'))->post(route('library.bookmark.store', $m))->assertForbidden();
    }

    public function test_revoked_cannot_be_bookmarked(): void
    {
        $m = $this->coreCurrent();
        DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => LearningValidityStatus::Revoked->value]);
        $this->actingAs($this->user('pegawai'))->post(route('library.bookmark.store', $m))->assertForbidden();
    }

    public function test_superseded_cannot_be_bookmarked(): void
    {
        $m = $this->coreCurrent();
        DB::table('learning_materials')->where('id', $m->id)->update(['validity_status' => LearningValidityStatus::Superseded->value]);
        $this->actingAs($this->user('pegawai'))->post(route('library.bookmark.store', $m))->assertForbidden();
    }

    public function test_existing_bookmark_survives_lifecycle_change(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $m->update(['publication_status' => LearningPublicationStatus::Draft->value]);
        $this->assertDatabaseHas('library_bookmarks', ['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $this->actingAs($u)->get(route('library.read', $m))->assertForbidden();
    }

    public function test_bookmark_usable_again_when_current(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $m->update(['publication_status' => LearningPublicationStatus::Draft->value]);
        $this->actingAs($u)->get(route('library.read', $m))->assertForbidden();
        $m->update(['publication_status' => LearningPublicationStatus::Published->value, 'published_at' => now()]);
        $this->actingAs($u)->get(route('library.read', $m))->assertOk();
        $this->assertDatabaseHas('library_bookmarks', ['user_id' => $u->id]);
    }

    // PROGRESS INDEPENDENCE
    public function test_bookmark_without_progress(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $this->assertSame(0, ReadingProgress::count());
        $this->assertSame(1, LibraryBookmark::count());
    }

    public function test_progress_without_bookmark(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 30]);
        $this->assertSame(1, ReadingProgress::count());
        $this->assertSame(0, LibraryBookmark::count());
    }

    public function test_bookmark_plus_progress(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 50]);
        $this->assertSame(1, LibraryBookmark::count());
        $this->assertSame(1, ReadingProgress::count());
    }

    public function test_progress_update_does_not_remove_bookmark(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $this->actingAs($u)->post(route('library.progress.update', $m), ['progress_percent' => 55])->assertOk();
        $this->assertDatabaseHas('library_bookmarks', ['user_id' => $u->id]);
    }

    public function test_bookmark_removal_does_not_remove_progress(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m->id, 'progress_percent' => 60]);
        $this->actingAs($u)->delete(route('library.bookmark.destroy', $m))->assertRedirect();
        $this->assertDatabaseHas('reading_progress', ['user_id' => $u->id]);
    }

    // UI / READER
    public function test_reader_shows_unbookmarked_state(): void
    {
        $m = $this->currentMaterial(['title' => 'Reader Unbookmarked Check']);
        $u = $this->user('pegawai');
        $resp = $this->actingAs($u)->get(route('library.read', $m));
        $resp->assertOk();
        $this->assertStringContainsString('☆ Simpan', $resp->getContent());
        $this->assertStringNotContainsString('★ Tersimpan', $resp->getContent());
    }

    public function test_reader_shows_bookmarked_state(): void
    {
        $m = $this->currentMaterial(['title' => 'Reader Bookmarked Check']);
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $resp = $this->actingAs($u)->get(route('library.read', $m));
        $resp->assertOk();
        $this->assertStringContainsString('★ Tersimpan', $resp->getContent());
    }

    public function test_index_no_n_plus_one(): void
    {
        $m1 = $this->currentMaterial(['title' => 'NPlusOne A']);
        $m2 = $this->currentMaterial(['title' => 'NPlusOne B']);
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m1->id]);
        DB::enableQueryLog();
        $this->actingAs($u)->get(route('library.index'))->assertOk();
        $queries = DB::getQueryLog();
        $bookmarkQueries = array_filter($queries, fn ($q) => str_contains($q['query'] ?? '', 'library_bookmarks'));
        $this->assertLessThanOrEqual(1, count($bookmarkQueries), 'N+1 bookmark query detected');
    }

    public function test_bookmark_page_only_own(): void
    {
        $m1 = $this->currentMaterial(['title' => 'Own Bookmark Mat']);
        $m2 = $this->currentMaterial(['title' => 'Other Bookmark Mat']);
        $a = $this->user('pegawai');
        $b = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $a->id, 'learning_material_id' => $m1->id]);
        LibraryBookmark::create(['user_id' => $b->id, 'learning_material_id' => $m2->id]);
        $resp = $this->actingAs($a)->get(route('library.bookmarks'));
        $resp->assertOk();
        $this->assertStringContainsString('Own Bookmark Mat', $resp->getContent());
        $this->assertStringNotContainsString('Other Bookmark Mat', $resp->getContent());
    }

    public function test_bookmark_page_handles_non_current_safely(): void
    {
        $m = $this->currentMaterial(['title' => 'NonCurrent Bookmark Page']);
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $m->update(['publication_status' => LearningPublicationStatus::Draft->value]);
        $resp = $this->actingAs($u)->get(route('library.bookmarks'));
        $resp->assertOk();
        $this->assertStringContainsString('NonCurrent Bookmark Page', $resp->getContent());
        $this->assertStringContainsString('Tidak tersedia', $resp->getContent());
    }

    public function test_two_users_can_bookmark_same_material(): void
    {
        $m = $this->currentMaterial();
        $a = $this->user('pegawai');
        $b = $this->user('pegawai');
        $this->actingAs($a)->post(route('library.bookmark.store', $m))->assertRedirect();
        $this->actingAs($b)->post(route('library.bookmark.store', $m))->assertRedirect();
        $this->assertDatabaseHas('library_bookmarks', ['user_id' => $a->id, 'learning_material_id' => $m->id]);
        $this->assertDatabaseHas('library_bookmarks', ['user_id' => $b->id, 'learning_material_id' => $m->id]);
        $this->assertSame(2, LibraryBookmark::where('learning_material_id', $m->id)->count());
    }

    public function test_guest_cannot_destroy_bookmark(): void
    {
        $m = $this->currentMaterial();
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $this->delete(route('library.bookmark.destroy', $m))->assertRedirect(route('login'));
        $this->assertDatabaseHas('library_bookmarks', ['user_id' => $u->id]);
    }

    public function test_bookmarks_non_current_does_not_expose_baca(): void
    {
        $m = $this->currentMaterial(['title' => 'Leak Check Mat']);
        $u = $this->user('pegawai');
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $m->update(['publication_status' => LearningPublicationStatus::Draft->value]);
        $resp = $this->actingAs($u)->get(route('library.bookmarks'));
        $html = $resp->getContent();
        $this->assertStringContainsString('Tidak tersedia', $html);
        $this->assertStringNotContainsString(route('library.read', $m), $html);
    }

    public function test_bookmarks_pagination(): void
    {
        $u = $this->user('pegawai');
        for ($i = 0; $i < 13; $i++) {
            $m = $this->currentMaterial(['title' => "Paginate Mat {$i}"]);
            LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        }
        $resp = $this->actingAs($u)->get(route('library.bookmarks'));
        $resp->assertOk();
        $this->assertStringContainsString('pagination', strtolower($resp->getContent()));
    }

    public function test_bookmarks_progress_no_n_plus_one(): void
    {
        $u = $this->user('pegawai');
        $m1 = $this->currentMaterial(['title' => 'Prog NPlus A']);
        $m2 = $this->currentMaterial(['title' => 'Prog NPlus B']);
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m1->id]);
        LibraryBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m2->id]);
        ReadingProgress::create(['user_id' => $u->id, 'learning_material_id' => $m1->id, 'progress_percent' => 55]);
        DB::enableQueryLog();
        $resp = $this->actingAs($u)->get(route('library.bookmarks'));
        $resp->assertOk();
        $this->assertStringContainsString('55%', $resp->getContent());
        $queries = DB::getQueryLog();
        $progressQueries = array_filter($queries, fn ($q) => str_contains($q['query'] ?? '', 'reading_progress'));
        $this->assertLessThanOrEqual(1, count($progressQueries), 'N+1 progress query on bookmarks page');
    }

    public function test_learning_bookmark_alias_works(): void
    {
        $u = $this->user('pegawai');
        $m = $this->currentMaterial();
        $bm = \App\Models\LearningBookmark::create(['user_id' => $u->id, 'learning_material_id' => $m->id]);
        $this->assertDatabaseHas('library_bookmarks', ['id' => $bm->id]);
        $this->assertTrue($bm->user->is($u));
        $this->assertTrue($bm->learningMaterial->is($m));
    }
}
