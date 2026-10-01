<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_logged_with_correct_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $a = Announcement::create(['title' => 'T', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => null, 'created_by' => $admin->id]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'model_type' => Announcement::class, 'model_id' => $a->id, 'user_id' => $admin->id]);
    }

    public function test_updated_logs_without_leaking_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $u = User::factory()->create(['role' => 'pegawai']);

        ActivityLog::where('model_type', User::class)->where('model_id', $u->id)->delete();
        $u->update(['password' => 'new-secret-123']);

        $log = ActivityLog::where('model_type', User::class)->where('model_id', $u->id)->where('action', 'updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('new-secret-123', $log->description);
        $this->assertStringContainsString('password diubah', $log->description);
    }

    public function test_deleted_logged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $a = Announcement::create(['title' => 'T', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => null, 'created_by' => $admin->id]);
        $a->delete();

        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'model_type' => Announcement::class, 'model_id' => $a->id]);
    }

    public function test_admin_can_view_log_aktivitas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('activity-log.index'))->assertStatus(200);
    }

    public function test_non_admin_cannot_view_log_aktivitas(): void
    {
        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'IT']);
        $this->actingAs($atasan)->get(route('activity-log.index'))->assertStatus(403);

        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $this->actingAs($pegawai)->get(route('activity-log.index'))->assertStatus(403);
    }

    public function test_guest_redirected_from_log_aktivitas(): void
    {
        $this->get(route('activity-log.index'))->assertRedirect(route('login'));
    }
}
