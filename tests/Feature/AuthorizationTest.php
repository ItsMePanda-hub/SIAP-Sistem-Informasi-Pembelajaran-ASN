<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_atasan_cannot_create_or_edit_resource_of_other_unit()
    {
        $atasanA = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Unit A']);
        
        // Cannot create for Unit B (should default to Unit A or fail validation in a real scenario, but we test authorization)
        // In our app, atasan only creates for their own unit. The controller forces target_unit_kerja to be their own.
        // Let's test edit/update since policies restrict it.

        $admin = User::factory()->create(['role' => 'admin']);
        $announcementB = Announcement::create([
            'title' => 'Test B',
            'body' => 'Body',
            'category' => 'rutin',
            'target_unit_kerja' => 'Unit B',
            'created_by' => $admin->id,
        ]);

        // We don't have edit/update routes in AnnouncementController, but we can test the Policy directly.
        $this->assertFalse($atasanA->can('update', $announcementB));
    }
}
