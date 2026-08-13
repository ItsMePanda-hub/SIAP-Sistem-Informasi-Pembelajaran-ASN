<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_completion_is_recorded_and_certificate_generated()
    {
        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $admin = User::factory()->create(['role' => 'admin']);

        $training = Training::create([
            'title' => 'Training A',
            'created_by' => $admin->id,
        ]);

        // Start
        $this->actingAs($pegawai)->post(route('trainings.start', $training));
        $this->assertDatabaseHas('training_progresses', [
            'training_id' => $training->id,
            'user_id' => $pegawai->id,
            'status' => 'sedang_berjalan',
        ]);

        // Complete
        $this->actingAs($pegawai)->post(route('trainings.complete', $training));
        $this->assertDatabaseHas('training_progresses', [
            'training_id' => $training->id,
            'user_id' => $pegawai->id,
            'status' => 'selesai',
        ]);
        
        $progress = $training->progressFor($pegawai);
        $this->assertNotNull($progress->certificate_code);
    }
}
