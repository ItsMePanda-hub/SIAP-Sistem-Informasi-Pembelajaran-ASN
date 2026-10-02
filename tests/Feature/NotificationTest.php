<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ExamOption;
use App\Models\Letter;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_notification_endpoints(): void
    {
        $this->getJson('/notifications')->assertStatus(401);
        $this->getJson('/notifications/unread-count')->assertStatus(401);
        $this->postJson('/notifications/read-all')->assertStatus(401);
        $this->postJson('/notifications/fake-uuid/read')->assertStatus(401);
    }

    public function test_user_can_list_own_notifications(): void
    {
        $user = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $admin = User::factory()->create(['role' => 'admin']);
        $ann = Announcement::create(['title' => 'Info A', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'IT', 'created_by' => $admin->id]);

        $this->actingAs($admin)->post(route('announcements.store'), ['title' => 'Info B', 'body' => 'Body', 'category' => 'rutin', 'target_unit_kerja' => 'IT']);

        $response = $this->actingAs($user)->getJson(route('notifications.index'));
        $response->assertOk()->assertJsonStructure(['data', 'current_page', 'last_page', 'per_page', 'total']);
        $first = $response->json('data')[0] ?? null;
        if ($first) {
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('type', $first);
            $this->assertArrayHasKey('title', $first);
            $this->assertArrayHasKey('message', $first);
            $this->assertArrayHasKey('url', $first);
            $this->assertArrayHasKey('read_at', $first);
            $this->assertArrayHasKey('created_at', $first);
        }
    }

    public function test_unread_count_and_mark_read(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang A']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('announcements.store'), ['title' => 'N1', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'Bidang A']);
        $this->actingAs($admin)->post(route('announcements.store'), ['title' => 'N2', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'Bidang A']);

        $this->actingAs($pegawai)->getJson(route('notifications.unread-count'))->assertJson(['count' => 2]);

        $list = $this->actingAs($pegawai)->getJson(route('notifications.index'))->json('data');
        $firstId = $list[0]['id'];

        $this->actingAs($pegawai)->postJson(route('notifications.read', $firstId))->assertOk();
        $this->actingAs($pegawai)->getJson(route('notifications.unread-count'))->assertJson(['count' => 1]);

        $this->actingAs($pegawai)->postJson(route('notifications.read', $firstId))->assertOk();
        $this->actingAs($pegawai)->getJson(route('notifications.unread-count'))->assertJson(['count' => 1]);
    }

    public function test_mark_all_as_read(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Bidang A']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('announcements.store'), ['title' => 'N1', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'Bidang A']);
        $this->actingAs($admin)->post(route('announcements.store'), ['title' => 'N2', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'Bidang A']);

        $this->actingAs($pegawai)->postJson(route('notifications.read-all'))->assertOk()->assertJson(['success' => true]);
        $this->actingAs($pegawai)->getJson(route('notifications.unread-count'))->assertJson(['count' => 0]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $pegawai->id, 'read_at' => null]);
    }

    public function test_user_cannot_mark_others_notification(): void
    {
        $userA = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $userB = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('announcements.store'), ['title' => 'Secret', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'IT']);

        $id = $userA->notifications()->first()->id;
        $this->actingAs($userB)->postJson(route('notifications.read', $id))->assertStatus(404);
        $this->assertDatabaseHas('notifications', ['id' => $id, 'read_at' => null]);
    }

    public function test_notification_stored_in_database(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit X']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('announcements.store'), ['title' => 'DB Check', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'Unit X']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $pegawai->id, 'notifiable_type' => User::class]);
        $n = $pegawai->notifications()->first();
        $this->assertEquals('announcement.created', $n->data['type']);
        $this->assertNotEmpty($n->data['title']);
        $this->assertNotEmpty($n->data['url']);
    }

    public function test_announcement_target_receives_and_outside_not(): void
    {
        $pegawaiA = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit A']);
        $pegawaiB = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'Unit B']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('announcements.store'), ['title' => 'Unit A Only', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'Unit A']);
        $this->assertEquals(1, $pegawaiA->notifications()->count());
        $this->assertEquals(0, $pegawaiB->notifications()->count());
    }

    public function test_announcement_broadcast_to_all_when_target_null(): void
    {
        $pegawaiA = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'A']);
        $pegawaiB = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'B']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('announcements.store'), ['title' => 'Broadcast', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => null]);
        $this->assertEquals(1, $pegawaiA->notifications()->count());
        $this->assertEquals(1, $pegawaiB->notifications()->count());
    }

    public function test_letter_visibility(): void
    {
        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'IT']);
        $pegawaiIT = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $pegawaiHR = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'HR']);

        $this->actingAs($atasan)->post(route('letters.store'), [
            'title' => 'Surat IT', 'category' => 'sk', 'file' => \Illuminate\Http\UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            'visibility' => 'unit', 'target_unit_kerja' => 'IT',
        ]);

        $this->assertEquals(1, $pegawaiIT->fresh()->notifications()->count());
        $this->assertEquals(0, $pegawaiHR->fresh()->notifications()->count());
    }

    public function test_task_assigned_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawaiIT = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $pegawaiHR = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'HR']);

        $this->actingAs($admin)->post(route('tasks.store'), [
            'title' => 'Tugas IT', 'description' => 'D', 'deadline' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'target_type' => 'bidang', 'target_unit_kerja' => 'IT',
        ]);

        $this->assertEquals(1, $pegawaiIT->notifications()->where('data->type', 'workspace.task_assigned')->count());
        $this->assertEquals(0, $pegawaiHR->notifications()->count());
        $data = $pegawaiIT->notifications()->first()->data;
        $this->assertEquals('workspace.task_assigned', $data['type']);
        $this->assertStringContainsString('/workspace/', $data['url']);
    }

    public function test_task_reviewed_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'IT']);

        $task = Task::create(['title' => 'T', 'description' => 'D', 'deadline' => now()->addDays(2), 'target_type' => 'individu', 'created_by' => $admin->id]);
        $assignment = TaskAssignment::create(['task_id' => $task->id, 'user_id' => $pegawai->id, 'status' => 'menunggu_review', 'submission_path' => 'x', 'submitted_at' => now()]);

        $this->actingAs($atasan)->post(route('tasks.review', $assignment), ['decision' => 'selesai']);

        $this->assertEquals(1, $pegawai->notifications()->where('data->type', 'workspace.task_reviewed')->count());
        $adminNotifs = $admin->notifications()->where('data->type', 'workspace.task_reviewed')->count();
        $this->assertEquals(0, $adminNotifs);
    }

    public function test_task_submitted_idempotency(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'IT']);

        $task = Task::create(['title' => 'T2', 'description' => 'D', 'deadline' => now()->addDays(2), 'target_type' => 'individu', 'created_by' => $admin->id]);
        $assignment = TaskAssignment::create(['task_id' => $task->id, 'user_id' => $pegawai->id, 'status' => 'belum_dikerjakan']);

        \Illuminate\Support\Facades\Storage::fake('public');
        $file = \Illuminate\Http\UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');

        $this->actingAs($pegawai)->post(route('tasks.submit', $assignment), ['file' => $file]);
        $countAfterFirst = $atasan->notifications()->where('data->type', 'workspace.task_submitted')->count();
        $this->assertEquals(1, $countAfterFirst);

        $assignment->refresh();
        $assignment->update(['status' => 'menunggu_review']);
        $file2 = \Illuminate\Http\UploadedFile::fake()->create('doc2.pdf', 10, 'application/pdf');
        $this->actingAs($pegawai)->post(route('tasks.submit', $assignment), ['file' => $file2]);
        $countAfterSecond = $atasan->fresh()->notifications()->where('data->type', 'workspace.task_submitted')->count();
        $this->assertEquals(1, $countAfterSecond);
    }

    public function test_exam_result_available_only_to_participant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $other = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);

        $exam = Exam::create(['title' => 'Ujian X', 'max_violations' => 3, 'created_by' => $admin->id, 'target_unit_kerja' => null]);
        $q = ExamQuestion::create(['exam_id' => $exam->id, 'type' => 'pilihan_ganda', 'question' => 'Q', 'order' => 1]);
        $opt = ExamOption::create(['exam_question_id' => $q->id, 'option_text' => 'A', 'is_correct' => true]);

        $attempt = ExamAttempt::create(['exam_id' => $exam->id, 'user_id' => $pegawai->id, 'status' => 'sedang_berjalan', 'started_at' => now()]);
        $this->actingAs($pegawai)->postJson(route('exams.answer', $exam), ['exam_question_id' => $q->id, 'exam_option_id' => $opt->id]);
        $this->actingAs($pegawai)->post(route('exams.submit', $exam));

        $this->assertEquals(1, $pegawai->notifications()->where('data->type', 'exam.result_available')->count());
        $this->assertEquals(0, $other->notifications()->where('data->type', 'exam.result_available')->count());
    }

    public function test_exam_available_targeting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawaiIT = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $pegawaiHR = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'HR']);

        $this->actingAs($admin)->post(route('exams.store'), [
            'title' => 'Ujian IT', 'max_violations' => 3, 'target_unit_kerja' => 'IT',
            'questions' => [['type' => 'pilihan_ganda', 'question' => 'Q?', 'options' => [['text' => 'A'], ['text' => 'B']], 'correct_index' => 0]],
        ]);

        $this->assertEquals(1, $pegawaiIT->notifications()->where('data->type', 'exam.available')->count());
        $this->assertEquals(0, $pegawaiHR->notifications()->where('data->type', 'exam.available')->count());
    }

    public function test_pagination_and_ownership_strict(): void
    {
        $user = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $admin = User::factory()->create(['role' => 'admin']);
        for ($i = 0; $i < 16; $i++) {
            $ann = Announcement::create(['title' => "N $i", 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'IT', 'created_by' => $admin->id]);
            $user->notify(new \App\Notifications\NewAnnouncement($ann));
        }

        $response = $this->actingAs($user)->getJson(route('notifications.index') . '?page=1');
        $response->assertOk();
        $this->assertEquals(15, count($response->json('data')));
        $this->assertEquals(16, $response->json('total'));

        $other = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $this->actingAs($admin)->post(route('announcements.store'), ['title' => 'Other target', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'IT']);
        $this->assertEquals(1, $other->notifications()->count());
        $this->assertEquals(17, $user->notifications()->count());
    }
}
