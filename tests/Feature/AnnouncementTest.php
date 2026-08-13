<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_atasan_a_cannot_see_announcement_target_unit_kerja_b()
    {
        $atasanA = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Unit A']);
        $admin = User::factory()->create(['role' => 'admin']);

        $announcementB = Announcement::create([
            'title' => 'Test B',
            'body' => 'Body B',
            'category' => 'rutin',
            'target_unit_kerja' => 'Unit B',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($atasanA)->get(route('announcements.show', $announcementB));

        $response->assertStatus(403);
    }

    public function test_read_receipt_is_recorded_when_announcement_is_opened()
    {
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit A']);
        $admin = User::factory()->create(['role' => 'admin']);

        $announcement = Announcement::create([
            'title' => 'Test A',
            'body' => 'Body A',
            'category' => 'rutin',
            'target_unit_kerja' => 'Unit A',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($pegawai)->get(route('announcements.show', $announcement));

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $pegawai->id,
        ]);
    }
}
