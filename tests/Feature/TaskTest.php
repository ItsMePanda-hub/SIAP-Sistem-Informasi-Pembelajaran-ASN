<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_task_bidang_and_individu()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai1 = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang IT']);
        $pegawai2 = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang IT']);
        $pegawai3 = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang Humas']);

        // Task Bidang
        $this->actingAs($admin)->post(route('tasks.store'), [
            'title' => 'Task Bidang IT',
            'description' => 'Instruksi bidang IT',
            'deadline' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'target_type' => 'bidang',
            'target_unit_kerja' => 'Bidang IT',
        ])->assertRedirect(route('tasks.index'));

        $taskBidang = Task::where('title', 'Task Bidang IT')->first();
        $this->assertNotNull($taskBidang);
        $this->assertEquals(2, $taskBidang->assignments()->count());
        $this->assertTrue($taskBidang->assignments()->where('user_id', $pegawai1->id)->exists());
        $this->assertTrue($taskBidang->assignments()->where('user_id', $pegawai2->id)->exists());
        $this->assertFalse($taskBidang->assignments()->where('user_id', $pegawai3->id)->exists());

        // Task Individu
        $this->actingAs($admin)->post(route('tasks.store'), [
            'title' => 'Task Individu Pegawai 3',
            'description' => 'Instruksi khusus pegawai 3',
            'deadline' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'target_type' => 'individu',
            'user_ids' => [$pegawai3->id],
        ])->assertRedirect(route('tasks.index'));

        $taskIndividu = Task::where('title', 'Task Individu Pegawai 3')->first();
        $this->assertNotNull($taskIndividu);
        $this->assertEquals(1, $taskIndividu->assignments()->count());
        $this->assertTrue($taskIndividu->assignments()->where('user_id', $pegawai3->id)->exists());
    }

    public function test_atasan_can_create_task_for_own_unit_but_cannot_for_other_unit()
    {
        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Bidang IT']);
        $pegawaiIT = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang IT']);

        // Berhasil buat untuk unit kerja sendiri
        $this->actingAs($atasan)->post(route('tasks.store'), [
            'title' => 'Task Internal IT',
            'description' => 'Instruksi internal',
            'deadline' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'target_type' => 'bidang',
            'target_unit_kerja' => 'Bidang IT',
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', ['title' => 'Task Internal IT', 'target_unit_kerja' => 'Bidang IT']);

        // Gagal jika atasan coba menargetkan bidang lain
        $this->actingAs($atasan)->post(route('tasks.store'), [
            'title' => 'Task Liar Bidang Humas',
            'description' => 'Instruksi liar',
            'deadline' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'target_type' => 'bidang',
            'target_unit_kerja' => 'Bidang Humas',
        ])->assertSessionHasErrors('target_unit_kerja');

        $this->assertDatabaseMissing('tasks', ['title' => 'Task Liar Bidang Humas']);
    }

    public function test_atasan_cannot_create_individual_task_for_users_of_different_unit()
    {
        $atasanIT = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Bidang IT']);
        $pegawaiHumas = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang Humas']);

        $this->actingAs($atasanIT)->post(route('tasks.store'), [
            'title' => 'Task Individu Pegawai Humas',
            'description' => 'Instruksi tidak sah',
            'deadline' => now()->addDays(4)->format('Y-m-d H:i:s'),
            'target_type' => 'individu',
            'user_ids' => [$pegawaiHumas->id],
        ])->assertSessionHasErrors('user_ids');

        $this->assertDatabaseMissing('tasks', ['title' => 'Task Individu Pegawai Humas']);
    }

    public function test_pegawai_can_view_assigned_task_and_cannot_view_unassigned_task()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawaiA = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang IT']);
        $pegawaiB = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang Humas']);

        $task = Task::create([
            'title' => 'Task Rahasia IT',
            'description' => 'Detail rahasia',
            'deadline' => now()->addDays(2),
            'target_type' => 'individu',
            'created_by' => $admin->id,
        ]);

        TaskAssignment::create([
            'task_id' => $task->id,
            'user_id' => $pegawaiA->id,
            'status' => 'belum_dikerjakan',
        ]);

        // Pegawai A yang ditugaskan bisa melihat
        $this->actingAs($pegawaiA)->get(route('tasks.show', $task))->assertStatus(200);

        // Pegawai B yang tidak ditugaskan ditolak 403
        $this->actingAs($pegawaiB)->get(route('tasks.show', $task))->assertStatus(403);
    }

    public function test_pegawai_can_submit_file_and_is_late_is_calculated_correctly()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang IT']);

        // 1. Task tepat waktu (deadline masa depan)
        $futureTask = Task::create([
            'title' => 'Task Tepat Waktu',
            'description' => 'Kumpulkan sebelum lusa',
            'deadline' => now()->addDays(2),
            'target_type' => 'individu',
            'created_by' => $admin->id,
        ]);

        $assignmentFuture = TaskAssignment::create([
            'task_id' => $futureTask->id,
            'user_id' => $pegawai->id,
            'status' => 'belum_dikerjakan',
        ]);

        $filePdf = UploadedFile::fake()->create('laporan.pdf', 500, 'application/pdf');

        $this->actingAs($pegawai)->post(route('tasks.submit', $assignmentFuture), [
            'file' => $filePdf,
            'note' => 'Laporan selesai tepat waktu.',
        ])->assertRedirect(route('tasks.show', $futureTask));

        $assignmentFuture->refresh();
        $this->assertEquals('menunggu_review', $assignmentFuture->status);
        $this->assertFalse($assignmentFuture->is_late);
        $this->assertNotNull($assignmentFuture->submission_path);
        Storage::disk('public')->assertExists($assignmentFuture->submission_path);

        // 2. Task terlambat (deadline masa lalu)
        $pastTask = Task::create([
            'title' => 'Task Terlambat',
            'description' => 'Deadline sudah lewat kemarin',
            'deadline' => now()->subDay(),
            'target_type' => 'individu',
            'created_by' => $admin->id,
        ]);

        $assignmentPast = TaskAssignment::create([
            'task_id' => $pastTask->id,
            'user_id' => $pegawai->id,
            'status' => 'belum_dikerjakan',
        ]);

        $fileZip = UploadedFile::fake()->create('berkas.zip', 1024, 'application/zip');

        $this->actingAs($pegawai)->post(route('tasks.submit', $assignmentPast), [
            'file' => $fileZip,
            'note' => 'Maaf terlambat.',
        ])->assertRedirect(route('tasks.show', $pastTask));

        $assignmentPast->refresh();
        $this->assertEquals('menunggu_review', $assignmentPast->status);
        $this->assertTrue($assignmentPast->is_late);
        $this->assertNotNull($assignmentPast->submission_path);
        Storage::disk('public')->assertExists($assignmentPast->submission_path);
    }

    public function test_submit_without_file_is_rejected()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang IT']);

        $task = Task::create([
            'title' => 'Task Validasi File',
            'description' => 'Harus ada file',
            'deadline' => now()->addDays(2),
            'target_type' => 'individu',
            'created_by' => $admin->id,
        ]);

        $assignment = TaskAssignment::create([
            'task_id' => $task->id,
            'user_id' => $pegawai->id,
            'status' => 'belum_dikerjakan',
        ]);

        $this->actingAs($pegawai)->post(route('tasks.submit', $assignment), [
            'note' => 'Hanya mengirim catatan tanpa berkas',
        ])->assertSessionHasErrors('file');

        $assignment->refresh();
        $this->assertEquals('belum_dikerjakan', $assignment->status);
    }

    public function test_admin_and_atasan_can_review_assignment()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $atasanIT = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Bidang IT']);
        $pegawaiIT = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang IT']);

        $task = Task::create([
            'title' => 'Task Review',
            'description' => 'Siap direview',
            'deadline' => now()->addDays(2),
            'target_type' => 'individu',
            'created_by' => $atasanIT->id,
        ]);

        $assignment = TaskAssignment::create([
            'task_id' => $task->id,
            'user_id' => $pegawaiIT->id,
            'status' => 'menunggu_review',
            'submission_path' => 'task-submissions/draft.pdf',
            'submitted_at' => now(),
        ]);

        // Atasan minta revisi
        $this->actingAs($atasanIT)->post(route('tasks.review', $assignment), [
            'decision' => 'revisi',
            'feedback' => 'Tolong lengkapi bab 2.',
        ])->assertRedirect(route('tasks.show', $task));

        $assignment->refresh();
        $this->assertEquals('revisi', $assignment->status);
        $this->assertEquals('Tolong lengkapi bab 2.', $assignment->feedback);
        $this->assertEquals($atasanIT->id, $assignment->reviewed_by);

        // Admin approve selesai
        $this->actingAs($admin)->post(route('tasks.review', $assignment), [
            'decision' => 'selesai',
            'feedback' => 'Sudah bagus.',
        ])->assertRedirect(route('tasks.show', $task));

        $assignment->refresh();
        $this->assertEquals('selesai', $assignment->status);
        $this->assertEquals($admin->id, $assignment->reviewed_by);
    }

    public function test_pegawai_cannot_review_assignment()
    {
        $pegawai1 = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang IT']);
        $pegawai2 = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang IT']);
        $admin = User::factory()->create(['role' => 'admin']);

        $task = Task::create([
            'title' => 'Task Pegawai Review Test',
            'description' => 'Test',
            'deadline' => now()->addDays(2),
            'target_type' => 'individu',
            'created_by' => $admin->id,
        ]);

        $assignment = TaskAssignment::create([
            'task_id' => $task->id,
            'user_id' => $pegawai2->id,
            'status' => 'menunggu_review',
        ]);

        // Pegawai 1 coba mereview assignment pegawai 2 -> 403
        $this->actingAs($pegawai1)->post(route('tasks.review', $assignment), [
            'decision' => 'selesai',
        ])->assertStatus(403);
    }

    public function test_download_submission_permissions()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $atasanIT = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Bidang IT']);
        $atasanHumas = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Bidang Humas']);
        $pegawaiIT = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang IT']);
        $pegawaiHumas = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang Humas']);

        $task = Task::create([
            'title' => 'Task Unduh Berkas',
            'description' => 'Test unduh',
            'deadline' => now()->addDays(2),
            'target_type' => 'individu',
            'created_by' => $admin->id,
        ]);

        $file = UploadedFile::fake()->create('dokumen.docx', 500);
        $path = $file->store('task-submissions', 'public');

        $assignment = TaskAssignment::create([
            'task_id' => $task->id,
            'user_id' => $pegawaiIT->id,
            'status' => 'menunggu_review',
            'submission_path' => $path,
            'submitted_at' => now(),
        ]);

        // Admin BISA download
        $this->actingAs($admin)->get(route('tasks.download', $assignment))->assertStatus(200);

        // Atasan IT (unit sama) BISA download
        $this->actingAs($atasanIT)->get(route('tasks.download', $assignment))->assertStatus(200);

        // Atasan Humas (unit beda) TIDAK BISA download (403)
        $this->actingAs($atasanHumas)->get(route('tasks.download', $assignment))->assertStatus(403);

        // Pegawai pemilik BISA download
        $this->actingAs($pegawaiIT)->get(route('tasks.download', $assignment))->assertStatus(200);

        // Pegawai lain TIDAK BISA download (403)
        $this->actingAs($pegawaiHumas)->get(route('tasks.download', $assignment))->assertStatus(403);
    }
}
