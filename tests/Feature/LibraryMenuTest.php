<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryMenuTest extends TestCase
{
    use RefreshDatabase;

    private function htmlForRole(string $role): string
    {
        $user = User::factory()->create(['role' => $role, 'email_verified_at' => now()]);

        return $this->actingAs($user)->get(route('dashboard'))->getContent();
    }

    public function test_all_roles_see_pembelajaran_siap_library_menu(): void
    {
        foreach (['admin', 'admin_library', 'pemilik', 'atasan', 'pegawai'] as $role) {
            $html = $this->htmlForRole($role);
            $this->assertStringContainsString('Pembelajaran', $html, "Menu Pembelajaran missing for {$role}");
            $this->assertStringContainsString('SIAP Library', $html, "SIAP Library missing for {$role}");
            $this->assertStringContainsString(route('library.index'), $html, "Library route missing for {$role}");
        }
    }

    public function test_kelola_library_only_for_managers_not_atasan_pegawai(): void
    {
        foreach (['admin', 'admin_library', 'pemilik'] as $role) {
            $html = $this->htmlForRole($role);
            $this->assertStringContainsString('Kelola Library', $html, "Kelola Library should show for {$role}");
        }

        foreach (['atasan', 'pegawai'] as $role) {
            $html = $this->htmlForRole($role);
            $this->assertStringNotContainsString('Kelola Library', $html, "Kelola Library should NOT show for {$role}");
        }
    }

    public function test_library_index_accessible_for_all_authenticated(): void
    {
        foreach (['admin', 'admin_library', 'pemilik', 'atasan', 'pegawai'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('library.index'))->assertOk();
        }
    }

    public function test_library_index_requires_auth(): void
    {
        $this->get(route('library.index'))->assertRedirect(route('login'));
    }
}
