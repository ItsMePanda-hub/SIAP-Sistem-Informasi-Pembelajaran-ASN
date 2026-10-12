<?php

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_admin_sistem_dapat_melihat_membuat_mengedit_mengelola_library(): void
    {
        $user = $this->user('admin');
        $material = LearningMaterial::factory()->create();

        $this->assertTrue($user->can('viewAny', LearningMaterial::class));
        $this->assertTrue($user->can('view', $material));
        $this->assertTrue($user->can('create', LearningMaterial::class));
        $this->assertTrue($user->can('update', $material));
        $this->assertTrue($user->can('delete', $material));
    }

    public function test_admin_library_dapat_melihat_membuat_mengedit_mengelola_library_tidak_super_admin(): void
    {
        $user = $this->user('admin_library');
        $material = LearningMaterial::factory()->create();

        $this->assertTrue($user->can('viewAny', LearningMaterial::class));
        $this->assertTrue($user->can('view', $material));
        $this->assertTrue($user->can('create', LearningMaterial::class));
        $this->assertTrue($user->can('update', $material));
        $this->assertTrue($user->can('delete', $material));

        $this->assertFalse($user->can('create', \App\Models\Task::class));
        $this->assertFalse($user->can('create', \App\Models\Announcement::class));
    }

    public function test_pemilik_dapat_melihat_membuat_mengedit_library(): void
    {
        $user = $this->user('pemilik');
        $material = LearningMaterial::factory()->create();

        $this->assertTrue($user->can('viewAny', LearningMaterial::class));
        $this->assertTrue($user->can('view', $material));
        $this->assertTrue($user->can('create', LearningMaterial::class));
        $this->assertTrue($user->can('update', $material));
        $this->assertTrue($user->can('delete', $material));
    }

    public function test_atasan_hanya_view_published_tidak_boleh_create_update_delete(): void
    {
        $user = $this->user('atasan');
        $published = LearningMaterial::factory()->custom()->create([
            'publication_status' => \App\Enums\LearningPublicationStatus::Published->value,
            'published_at' => now(),
        ]);
        $draft = LearningMaterial::factory()->custom()->create([
            'publication_status' => \App\Enums\LearningPublicationStatus::Draft->value,
            'published_at' => null,
        ]);

        $this->assertTrue($user->can('view', $published));
        $this->assertFalse($user->can('view', $draft));
        $this->assertFalse($user->can('create', LearningMaterial::class));
        $this->assertFalse($user->can('update', $draft));
        $this->assertFalse($user->can('delete', $draft));
    }

    public function test_pegawai_hanya_view_published_tidak_boleh_create_update_delete(): void
    {
        $user = $this->user('pegawai');
        $published = LearningMaterial::factory()->custom()->create([
            'publication_status' => \App\Enums\LearningPublicationStatus::Published->value,
            'published_at' => now(),
        ]);
        $draft = LearningMaterial::factory()->custom()->create([
            'publication_status' => \App\Enums\LearningPublicationStatus::Draft->value,
            'published_at' => null,
        ]);

        $this->assertTrue($user->can('view', $published));
        $this->assertFalse($user->can('view', $draft));
        $this->assertFalse($user->can('create', LearningMaterial::class));
        $this->assertFalse($user->can('update', $draft));
        $this->assertFalse($user->can('delete', $draft));
    }
}
