<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_tim_saya(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/tim-saya')->assertOk();
    }

    public function test_pemilik_can_access_tim_saya(): void
    {
        $pemilik = User::factory()->create(['role' => 'pemilik']);

        $this->actingAs($pemilik)->get('/tim-saya')->assertOk();
    }

    public function test_atasan_can_access_tim_saya(): void
    {
        $atasan = User::factory()->create(['role' => 'atasan']);

        $this->actingAs($atasan)->get('/tim-saya')->assertOk();
    }

    public function test_pegawai_cannot_access_tim_saya(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai']);

        $this->actingAs($pegawai)->get('/tim-saya')->assertForbidden();
    }

    public function test_admin_sees_pegawai_from_all_units(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'pegawai', 'name' => 'Pegawai Unit A', 'unit_kerja' => 'Unit A']);
        User::factory()->create(['role' => 'pegawai', 'name' => 'Pegawai Unit B', 'unit_kerja' => 'Unit B']);

        $response = $this->actingAs($admin)->get('/tim-saya');

        $response->assertOk();
        $response->assertSee('Pegawai Unit A');
        $response->assertSee('Pegawai Unit B');
    }

    public function test_atasan_only_sees_pegawai_from_own_unit(): void
    {
        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Unit A']);
        User::factory()->create(['role' => 'pegawai', 'name' => 'Pegawai Unit A', 'unit_kerja' => 'Unit A']);
        User::factory()->create(['role' => 'pegawai', 'name' => 'Pegawai Unit B', 'unit_kerja' => 'Unit B']);

        $response = $this->actingAs($atasan)->get('/tim-saya');

        $response->assertOk();
        $response->assertSee('Pegawai Unit A');
        $response->assertDontSee('Pegawai Unit B');
    }

    public function test_pemilik_can_view_kelola_pengguna_but_not_edit(): void
    {
        $pemilik = User::factory()->create(['role' => 'pemilik']);
        $target = User::factory()->create(['role' => 'pegawai']);

        $this->actingAs($pemilik)->get('/pengguna')->assertOk();
        $this->actingAs($pemilik)->get("/pengguna/{$target->id}/edit")->assertForbidden();
        $this->actingAs($pemilik)->put("/pengguna/{$target->id}", ['role' => 'atasan'])->assertForbidden();
    }
}
