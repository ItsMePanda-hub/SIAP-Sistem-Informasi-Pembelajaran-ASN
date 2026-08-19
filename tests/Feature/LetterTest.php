<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_restricted_letter_for_pegawai_is_visible_to_pegawai(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
            'unit_kerja' => 'IT',
        ]);
        $uploader = User::factory()->create([
            'role' => 'atasan',
            'unit_kerja' => 'IT',
        ]);
        $letter = Letter::create([
            'title' => 'Surat untuk Pegawai',
            'category' => 'sk',
            'visibility' => 'role',
            'target_role' => 'pegawai',
            'file_path' => 'letters/surat-pegawai.pdf',
            'uploaded_by' => $uploader->id,
        ]);

        $this->assertTrue($letter->isAccessibleBy($pegawai));

        $this->actingAs($pegawai)
            ->get(route('letters.index'))
            ->assertOk()
            ->assertSee('Surat untuk Pegawai');
    }
}
