<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialLifecycleLog;
use App\Models\LearningMaterialRightsLog;
use App\Models\LearningMaterialSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LibraryLifecycleAuditTest extends TestCase
{
    use RefreshDatabase;

    private function lc(): array { return [LearningLevel::factory()->create(), LearningCategory::factory()->create()]; }
    private function user(string $role): User { return User::factory()->create(['role' => $role]); }

    private function currentCustom(array $over = []): LearningMaterial
    {
        [$l,$c]= $this->lc();
        return LearningMaterial::factory()->custom()->native()->create(array_merge([
            'learning_level_id'=>$l->id,'learning_category_id'=>$c->id,
            'publication_status'=>'draft','published_at'=>null,
        ],$over));
    }

    private function coreValid(): LearningMaterial
    {
        [$l,$c]= $this->lc();
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id'=>$l->id,'learning_category_id'=>$c->id,
            'material_type'=>'native','content'=>'isi',
            'verification_status'=>'verified','validity_status'=>'active','publication_status'=>'draft',
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id'=>$m->id,'source_name'=>'S','publisher'=>'P']);
        return $m->fresh();
    }

    public function test_publish_valid_material_generates_log(): void
    {
        $admin = $this->user('admin_library');
        $m = $this->coreValid();
        $this->assertSame(0, LearningMaterialLifecycleLog::count());
        $this->actingAs($admin)->post(route('library.publish',$m))->assertRedirect(route('library.index'));
        $this->assertDatabaseHas('learning_material_lifecycle_logs',[
            'learning_material_id'=>$m->id,'field'=>'publication_status','old_value'=>'draft','new_value'=>'published',
        ]);
        $log = LearningMaterialLifecycleLog::where('learning_material_id',$m->id)->where('field','publication_status')->first();
        $this->assertSame($admin->id,$log->actor_user_id);
        $this->assertFalse((bool)$log->is_system);
        $this->assertSame($m->title,$log->title_snapshot);
    }

    public function test_unpublish_generates_log(): void
    {
        $admin=$this->user('admin_library');
        $m=$this->coreValid();
        $m->publish(); $m->refresh();
        LearningMaterialLifecycleLog::query()->delete();
        $this->actingAs($admin)->post(route('library.unpublish',$m))->assertRedirect();
        $this->assertDatabaseHas('learning_material_lifecycle_logs',[
            'learning_material_id'=>$m->id,'field'=>'publication_status','old_value'=>'published','new_value'=>'unpublished',
        ]);
    }

    public function test_verification_status_records_old_new(): void
    {
        $admin=$this->user('admin_library');
        $m=$this->currentCustom();
        $this->actingAs($admin)->put(route('library.update',$m),[
            'title'=>$m->title,'description'=>$m->description,'scope'=>'custom','material_type'=>$m->material_type->value,
            'learning_level_id'=>$m->learning_level_id,'learning_category_id'=>$m->learning_category_id,
            'publication_status'=>'draft','verification_status'=>'pending_review','validity_status'=>'unknown','content'=>'Konten verifikasi uji.',
        ])->assertRedirect();
        $this->assertDatabaseHas('learning_material_lifecycle_logs',[
            'learning_material_id'=>$m->id,'field'=>'verification_status','old_value'=>'draft','new_value'=>'pending_review',
        ]);
    }

    public function test_validity_status_records_old_new(): void
    {
        $admin=$this->user('admin_library');
        $m=$this->currentCustom(['validity_status'=>'unknown']);
        $this->actingAs($admin)->put(route('library.update',$m),[
            'title'=>$m->title,'description'=>$m->description,'scope'=>'custom','material_type'=>$m->material_type->value,
            'learning_level_id'=>$m->learning_level_id,'learning_category_id'=>$m->learning_category_id,
            'publication_status'=>'draft','verification_status'=>'draft','validity_status'=>'active','content'=>'x',
        ])->assertRedirect();
        $this->assertDatabaseHas('learning_material_lifecycle_logs',['learning_material_id'=>$m->id,'field'=>'validity_status','old_value'=>'unknown','new_value'=>'active']);
    }

    public function test_metadata_title_scope_level_category_logged(): void
    {
        $admin=$this->user('admin_library');
        [$l,$c]=$this->lc();
        $m=$this->currentCustom();
        $newLevel=LearningLevel::factory()->create();
        $newCat=LearningCategory::factory()->create();
        $this->actingAs($admin)->put(route('library.update',$m),[
            'title'=>'Judul Baru Audit','description'=>$m->description,'scope'=>'core','material_type'=>'native',
            'learning_level_id'=>$newLevel->id,'learning_category_id'=>$newCat->id,
            'publication_status'=>'draft','verification_status'=>'draft','validity_status'=>'unknown','content'=>'konten baru audit',
        ])->assertRedirect();
        $this->assertDatabaseHas('learning_material_lifecycle_logs',['learning_material_id'=>$m->id,'field'=>'title','new_value'=>'Judul Baru Audit']);
        $this->assertDatabaseHas('learning_material_lifecycle_logs',['learning_material_id'=>$m->id,'field'=>'scope','old_value'=>'custom','new_value'=>'core']);
        $this->assertDatabaseHas('learning_material_lifecycle_logs',['learning_material_id'=>$m->id,'field'=>'learning_level_id']);
        $this->assertDatabaseHas('learning_material_lifecycle_logs',['learning_material_id'=>$m->id,'field'=>'learning_category_id']);
    }

    public function test_file_path_change_logged(): void
    {
        $admin=$this->user('admin_library');
        [$l,$c]=$this->lc();
        $m=LearningMaterial::factory()->custom()->create([
            'learning_level_id'=>$l->id,'learning_category_id'=>$c->id,
            'material_type'=>'pdf','file_path'=>'library-pdfs/a.pdf','file_original_name'=>'a.pdf','file_mime'=>'application/pdf','file_size'=>1000,
            'publication_status'=>'draft',
        ]);
        $this->actingAs($admin);
        Auth::login($admin);
        $m->update(['file_path'=>'library-pdfs/b.pdf']);
        $this->assertDatabaseHas('learning_material_lifecycle_logs',['learning_material_id'=>$m->id,'field'=>'file_path','old_value'=>'library-pdfs/a.pdf','new_value'=>'library-pdfs/b.pdf']);
        Auth::logout();
    }

    public function test_unchanged_field_no_duplicate_log(): void
    {
        $admin=$this->user('admin_library');
        $m=$this->currentCustom();
        $this->actingAs($admin)->put(route('library.update',$m),[
            'title'=>$m->title,'description'=>$m->description,'scope'=>'custom','material_type'=>$m->material_type->value,
            'learning_level_id'=>$m->learning_level_id,'learning_category_id'=>$m->learning_category_id,
            'publication_status'=>'draft','verification_status'=>'draft','validity_status'=>'unknown','content'=>'x',
        ])->assertRedirect();
        $this->assertSame(0, LearningMaterialLifecycleLog::where('learning_material_id',$m->id)->where('field','title')->count());
        $this->assertSame(0, LearningMaterialLifecycleLog::where('learning_material_id',$m->id)->where('field','verification_status')->count());
    }

    public function test_failed_transition_no_fake_audit(): void
    {
        $admin=$this->user('admin_library');
        [$l,$c]=$this->lc();
        $m=LearningMaterial::factory()->core()->create([
            'learning_level_id'=>$l->id,'learning_category_id'=>$c->id,
            'verification_status'=>'draft','validity_status'=>'active','publication_status'=>'draft',
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id'=>$m->id,'source_name'=>'','publisher'=>'']);
        $before = LearningMaterialLifecycleLog::count();
        $this->actingAs($admin)->post(route('library.publish',$m))->assertSessionHasErrors('publication_status');
        $this->assertSame($before, LearningMaterialLifecycleLog::count());
        $this->assertSame('draft',$m->fresh()->publication_status->value);
    }

    public function test_rollback_cancels_log(): void
    {
        $m=$this->currentCustom(['title'=>'Rollback Awal']);
        $before = LearningMaterialLifecycleLog::count();
        try {
            DB::transaction(function () use ($m) {
                $m->update(['title'=>'Rollback Baru']);
                throw new \RuntimeException('rollback test');
            });
        } catch (\RuntimeException $e) {}
        $this->assertSame('Rollback Awal',$m->fresh()->title);
        $this->assertSame($before, LearningMaterialLifecycleLog::count());
    }

    public function test_actor_available_recorded(): void
    {
        $admin=$this->user('admin_library');
        $m=$this->currentCustom();
        $this->actingAs($admin)->put(route('library.update',$m),[
            'title'=>'Actor Test','description'=>$m->description,'scope'=>'custom','material_type'=>$m->material_type->value,
            'learning_level_id'=>$m->learning_level_id,'learning_category_id'=>$m->learning_category_id,
            'publication_status'=>'draft','verification_status'=>'draft','validity_status'=>'unknown','content'=>'x',
        ])->assertRedirect();
        $log=LearningMaterialLifecycleLog::where('learning_material_id',$m->id)->where('field','title')->first();
        $this->assertSame($admin->id,$log->actor_user_id);
        $this->assertFalse((bool)$log->is_system);
    }

    public function test_actor_not_available_no_failure(): void
    {
        $m=$this->currentCustom(['title'=>'NoActor']);
        Auth::logout();
        $m->update(['title'=>'NoActor Baru']);
        $log=LearningMaterialLifecycleLog::where('learning_material_id',$m->id)->where('field','title')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->actor_user_id);
    }

    public function test_system_flag_only_when_proven(): void
    {
        $admin=$this->user('admin_library');
        $m=$this->currentCustom();
        $this->actingAs($admin)->put(route('library.update',$m),[
            'title'=>'System Flag Test','description'=>$m->description,'scope'=>'custom','material_type'=>$m->material_type->value,
            'learning_level_id'=>$m->learning_level_id,'learning_category_id'=>$m->learning_category_id,
            'publication_status'=>'draft','verification_status'=>'draft','validity_status'=>'unknown','content'=>'x',
        ])->assertRedirect();
        $logs=LearningMaterialLifecycleLog::where('learning_material_id',$m->id)->get();
        foreach($logs as $l){ $this->assertFalse((bool)$l->is_system); $this->assertNull($l->reason); }
    }

    public function test_deleting_material_preserves_log(): void
    {
        $admin=$this->user('admin_library');
        $m=$this->currentCustom(['title'=>'Hapus Audit']);
        $this->actingAs($admin)->put(route('library.update',$m),[
            'title'=>'Hapus Audit Baru','description'=>$m->description,'scope'=>'custom','material_type'=>$m->material_type->value,
            'learning_level_id'=>$m->learning_level_id,'learning_category_id'=>$m->learning_category_id,
            'publication_status'=>'draft','verification_status'=>'draft','validity_status'=>'unknown','content'=>'x',
        ])->assertRedirect();
        $logId=LearningMaterialLifecycleLog::where('learning_material_id',$m->id)->first()->id;
        $this->actingAs($admin)->delete(route('library.destroy',$m))->assertRedirect();
        $this->assertDatabaseHas('learning_material_lifecycle_logs',['id'=>$logId,'learning_material_id'=>null]);
        $log=LearningMaterialLifecycleLog::find($logId);
        $this->assertSame('Hapus Audit Baru',$log->title_snapshot);
        $this->assertNotNull($log->slug_snapshot);
    }

    public function test_unauthorized_cannot_access_history(): void
    {
        $manager=$this->user('admin_library');
        $pegawai=$this->user('pegawai');
        $m=$this->currentCustom();
        $this->actingAs($manager)->put(route('library.update',$m),[
            'title'=>'Hist Secret','description'=>$m->description,'scope'=>'custom','material_type'=>$m->material_type->value,
            'learning_level_id'=>$m->learning_level_id,'learning_category_id'=>$m->learning_category_id,
            'publication_status'=>'draft','verification_status'=>'draft','validity_status'=>'unknown','content'=>'x',
        ])->assertRedirect();
        $this->actingAs($pegawai)->get(route('library.source.edit',$m))->assertForbidden();
        LearningMaterialLifecycleLog::create([
            'learning_material_id'=>$m->id,'title_snapshot'=>$m->title,'slug_snapshot'=>$m->slug,
            'actor_user_id'=>$manager->id,'field'=>'title','old_value'=>'Hist Secret','new_value'=>'Hist Secret Baru','reason'=>'INTERNAL_SECRET_LIFECYCLE_999','is_system'=>false,
        ]);
        $this->actingAs($pegawai)->get(route('library.index'))->assertOk();
        $this->assertStringNotContainsString('INTERNAL_SECRET_LIFECYCLE_999',$this->actingAs($pegawai)->get(route('library.index'))->getContent());
        $this->assertStringNotContainsString('INTERNAL_SECRET_LIFECYCLE_999',$this->actingAs($pegawai)->get(route('library.read',$m))->getContent());
    }

    public function test_consumer_html_not_leak_history(): void
    {
        $manager=$this->user('admin_library');
        $pegawai=$this->user('pegawai');
        $m=$this->currentCustom(['publication_status'=>'published','published_at'=>now()]);
        LearningMaterialLifecycleLog::create([
            'learning_material_id'=>$m->id,'title_snapshot'=>$m->title,'slug_snapshot'=>$m->slug,
            'actor_user_id'=>$manager->id,'field'=>'verification_status','old_value'=>'draft','new_value'=>'verified','reason'=>'SECRET_REASON_LIFECYCLE','is_system'=>false,
        ]);
        $this->actingAs($pegawai)->get(route('library.index'))->assertOk();
        $this->assertStringNotContainsString('SECRET_REASON_LIFECYCLE',$this->actingAs($pegawai)->get(route('library.index'))->getContent());
        $this->assertStringNotContainsString('SECRET_REASON_LIFECYCLE',$this->actingAs($pegawai)->get(route('library.read',$m))->getContent());
    }

    public function test_rights_review_not_duplicated(): void
    {
        $manager=$this->user('admin_library');
        $m=$this->currentCustom();
        $beforeLifecycle=LearningMaterialLifecycleLog::count();
        $beforeRights=LearningMaterialRightsLog::count();
        $this->actingAs($manager)->put(route('library.source.update',$m),[
            'source_name'=>'S','publisher'=>'P','license_type'=>'cc-by-4.0','license_url'=>'https://example.com/lic','rights_status'=>'approved',
        ])->assertRedirect();
        $this->assertSame($beforeLifecycle, LearningMaterialLifecycleLog::count());
        $this->assertSame($beforeRights+1, LearningMaterialRightsLog::count());
    }

    public function test_activity_logs_unchanged(): void
    {
        $admin=$this->user('admin_library');
        $before=ActivityLog::count();
        $m=$this->currentCustom();
        $this->actingAs($admin)->put(route('library.update',$m),[
            'title'=>'Activity Unchanged','description'=>$m->description,'scope'=>'custom','material_type'=>$m->material_type->value,
            'learning_level_id'=>$m->learning_level_id,'learning_category_id'=>$m->learning_category_id,
            'publication_status'=>'draft','verification_status'=>'draft','validity_status'=>'unknown','content'=>'x',
        ])->assertRedirect();
        $this->assertSame($before, ActivityLog::count());
    }

    public function test_no_regression_publication_gate(): void
    {
        $admin=$this->user('admin_library');
        [$l,$c]=$this->lc();
        $core=LearningMaterial::factory()->core()->create([
            'learning_level_id'=>$l->id,'learning_category_id'=>$c->id,
            'verification_status'=>'draft','validity_status'=>'active','publication_status'=>'draft',
        ]);
        LearningMaterialSource::factory()->create(['learning_material_id'=>$core->id,'source_name'=>'','publisher'=>'']);
        $this->actingAs($admin)->post(route('library.publish',$core))->assertSessionHasErrors('publication_status');
        $core->refresh();
        $this->assertSame('draft',$core->publication_status->value);
        $this->assertSame(0, LearningMaterialLifecycleLog::where('learning_material_id',$core->id)->where('field','publication_status')->count());
    }
}
