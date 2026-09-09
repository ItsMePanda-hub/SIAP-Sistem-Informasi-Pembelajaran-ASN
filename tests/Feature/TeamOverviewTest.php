<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pemilik_gets_all_pegawai_roster()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pemilik = User::factory()->create(['role' => 'pemilik']);
        
        // Buat beberapa pegawai di unit berbeda
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit A', 'email' => 'a@test.com', 'nip' => '111']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit B', 'email' => 'b@test.com', 'nip' => '222']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit C', 'email' => 'c@test.com', 'nip' => '333']);

        $service = new StatisticsService();
        
        $rosterAdmin = $service->getTeamOverviewForUser($admin);
        $rosterPemilik = $service->getTeamOverviewForUser($pemilik);

        $this->assertCount(3, $rosterAdmin);
        $this->assertCount(3, $rosterPemilik);
    }

    public function test_atasan_gets_only_own_unit_roster()
    {
        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Unit X']);
        
        // Pegawai di unit X
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit X', 'name' => 'Pegawai X']);
        // Pegawai di unit Y
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit Y', 'name' => 'Pegawai Y']);

        $service = new StatisticsService();
        $roster = $service->getTeamOverviewForUser($atasan);

        $this->assertCount(1, $roster);
        $this->assertEquals('Pegawai X', $roster[0]['name']);
    }

    public function test_pegawai_gets_empty_roster()
    {
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit Z']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit Z']);

        $service = new StatisticsService();
        $roster = $service->getTeamOverviewForUser($pegawai);

        $this->assertIsArray($roster);
        $this->assertEmpty($roster);
    }

    public function test_roster_does_not_contain_sensitive_fields()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'role' => 'pegawai', 
            'unit_kerja' => 'Unit P', 
            'email' => 'secret@test.com', 
            'nip' => '999999',
            'password' => 'hashedpassword'
        ]);

        $service = new StatisticsService();
        $roster = $service->getTeamOverviewForUser($admin);
        
        $this->assertNotEmpty($roster);
        $entry = $roster[0];

        $this->assertArrayHasKey('name', $entry);
        $this->assertArrayHasKey('unit_kerja', $entry);
        $this->assertArrayHasKey('role', $entry);
        $this->assertArrayHasKey('exam_completed', $entry);
        $this->assertArrayHasKey('training_progress_percent', $entry);
        $this->assertArrayHasKey('announcement_unread_count', $entry);

        $this->assertArrayNotHasKey('email', $entry);
        $this->assertArrayNotHasKey('nip', $entry);
        $this->assertArrayNotHasKey('password', $entry);
    }
}