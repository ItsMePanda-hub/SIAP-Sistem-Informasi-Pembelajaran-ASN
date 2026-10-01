<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $this->actingAs($pegawai)->post(route('trainings.start', $training));
        $this->assertDatabaseHas('training_progresses', [
            'training_id' => $training->id,
            'user_id' => $pegawai->id,
            'status' => 'sedang_berjalan',
        ]);

        $this->actingAs($pegawai)->post(route('trainings.complete', $training));
        $this->assertDatabaseHas('training_progresses', [
            'training_id' => $training->id,
            'user_id' => $pegawai->id,
            'status' => 'selesai',
        ]);

        $progress = $training->progressFor($pegawai);
        $this->assertNotNull($progress->certificate_code);
    }

    public function test_completion_percentage_null_target_counts_all_pegawai()
    {
        $admin = User::factory()->create(['role' => 'admin', 'unit_kerja' => 'Bidang A']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang A']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang A']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang B']);

        $pegawaiB = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang B']);

        $training = Training::create(['title' => 'Lintas Bidang', 'target_unit_kerja' => null, 'created_by' => $admin->id]);

        $training->progresses()->create(['user_id' => $pegawaiB->id, 'status' => 'selesai']);

        $this->assertEquals(25, $training->completionPercentage());
    }

    public function test_completion_percentage_with_target_counts_only_that_unit()
    {
        $admin = User::factory()->create(['role' => 'admin', 'unit_kerja' => 'Bidang A']);
        $pA1 = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang A']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang A']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang B']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang B']);

        $training = Training::create(['title' => 'Khusus A', 'target_unit_kerja' => 'Bidang A', 'created_by' => $admin->id]);

        $training->progresses()->create(['user_id' => $pA1->id, 'status' => 'selesai']);

        $this->assertEquals(50, $training->completionPercentage());
    }

    public function test_training_can_be_created_with_materi_pdf()
    {
        Storage::fake('public');

        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Bidang A']);
        $file = UploadedFile::fake()->create('materi.pdf', 100, 'application/pdf');

        $this->actingAs($atasan)->post(route('trainings.store'), [
            'title'       => 'Training Materi PDF',
            'description' => 'Deskripsi',
            'materi'      => $file,
        ])->assertRedirect(route('trainings.index'));

        $training = Training::where('title', 'Training Materi PDF')->first();
        $this->assertNotNull($training->material_path);
        Storage::disk('public')->assertExists($training->material_path);
    }

    public function test_training_can_be_created_with_materi_zip_and_downloaded()
    {
        Storage::fake('public');

        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Bidang A']);
        $file = UploadedFile::fake()->create('arsip_materi.zip', 2048, 'application/zip');

        $this->actingAs($atasan)->post(route('trainings.store'), [
            'title'       => 'Training Materi ZIP',
            'description' => 'Deskripsi',
            'materi'      => $file,
        ])->assertRedirect(route('trainings.index'));

        $training = Training::where('title', 'Training Materi ZIP')->first();
        $this->assertNotNull($training->material_path);
        Storage::disk('public')->assertExists($training->material_path);

        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang A']);
        $this->actingAs($pegawai)
             ->get(route('trainings.materi', $training))
             ->assertStatus(200);
    }

    public function test_training_can_be_created_with_materi_docx_and_downloaded()
    {
        Storage::fake('public');

        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Bidang A']);
        $file = UploadedFile::fake()->create('modul.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($atasan)->post(route('trainings.store'), [
            'title'       => 'Training Materi DOCX',
            'description' => 'Deskripsi',
            'materi'      => $file,
        ])->assertRedirect(route('trainings.index'));

        $training = Training::where('title', 'Training Materi DOCX')->first();
        $this->assertNotNull($training->material_path);
        Storage::disk('public')->assertExists($training->material_path);

        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang A']);
        $this->actingAs($pegawai)
             ->get(route('trainings.materi', $training))
             ->assertStatus(200);
    }

    public function test_training_with_disallowed_materi_type_is_rejected()
    {
        Storage::fake('public');

        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Bidang A']);
        $fileExe = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');
        $filePhp = UploadedFile::fake()->create('shell.php', 10, 'text/x-php');

        $this->actingAs($atasan)->post(route('trainings.store'), [
            'title'       => 'Training Exe',
            'description' => 'Deskripsi',
            'materi'      => $fileExe,
        ])->assertSessionHasErrors('materi');

        $this->actingAs($atasan)->post(route('trainings.store'), [
            'title'       => 'Training Php',
            'description' => 'Deskripsi',
            'materi'      => $filePhp,
        ])->assertSessionHasErrors('materi');

        $this->assertDatabaseMissing('trainings', ['title' => 'Training Exe']);
        $this->assertDatabaseMissing('trainings', ['title' => 'Training Php']);
    }

    public function test_authorized_user_can_download_materi()
    {
        Storage::fake('public');

        $admin  = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang A']);
        $file   = UploadedFile::fake()->create('materi.pdf', 100, 'application/pdf');
        $path   = $file->store('training-materials', 'public');

        $training = Training::create([
            'title'           => 'Training Download',
            'material_path'   => $path,
            'target_unit_kerja' => null,
            'created_by'      => $admin->id,
        ]);

        $this->actingAs($pegawai)
             ->get(route('trainings.materi', $training))
             ->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_download_materi()
    {
        Storage::fake('public');

        $admin   = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang B']);
        $file    = UploadedFile::fake()->create('materi.pdf', 100, 'application/pdf');
        $path    = $file->store('training-materials', 'public');

        $training = Training::create([
            'title'             => 'Training Rahasia',
            'material_path'     => $path,
            'target_unit_kerja' => 'Bidang A',
            'created_by'        => $admin->id,
        ]);

        $this->actingAs($pegawai)
             ->get(route('trainings.materi', $training))
             ->assertStatus(403);
    }
}
