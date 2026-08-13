<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Training;
use App\Models\TrainingProgress;
use App\Models\User;
use App\Services\ChatbotContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    // ── context scoping ────────────────────────────────────────────────────

    public function test_context_for_pengguna_only_contains_own_unit_kerja_announcements()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);

        // Visible to pegawai
        Announcement::create(['title' => 'Global', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => null, 'created_by' => $admin->id]);
        Announcement::create(['title' => 'IT Only', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'IT', 'created_by' => $admin->id]);
        // NOT visible
        Announcement::create(['title' => 'HR Only', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'HR', 'created_by' => $admin->id]);

        $context = (new ChatbotContextService())->buildContext($pegawai);

        $titles = array_column($context['announcements'], 'title');
        $this->assertContains('Global', $titles);
        $this->assertContains('IT Only', $titles);
        $this->assertNotContains('HR Only', $titles);
    }

    public function test_context_for_atasan_scoped_to_own_unit_kerja()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'Keuangan']);

        Training::create(['title' => 'Training Keuangan', 'target_unit_kerja' => 'Keuangan', 'created_by' => $admin->id]);
        Training::create(['title' => 'Training IT', 'target_unit_kerja' => 'IT', 'created_by' => $admin->id]);
        Training::create(['title' => 'Training Semua', 'target_unit_kerja' => null, 'created_by' => $admin->id]);

        $context = (new ChatbotContextService())->buildContext($atasan);

        $titles = array_column($context['trainings'], 'judul');
        $this->assertContains('Training Keuangan', $titles);
        $this->assertContains('Training Semua', $titles);
        $this->assertNotContains('Training IT', $titles);
    }

    public function test_context_for_admin_sees_all_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'admin']);

        Announcement::create(['title' => 'For IT', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'IT', 'created_by' => $other->id]);
        Announcement::create(['title' => 'For HR', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'HR', 'created_by' => $other->id]);

        $context = (new ChatbotContextService())->buildContext($admin);

        $titles = array_column($context['announcements'], 'title');
        $this->assertContains('For IT', $titles);
        $this->assertContains('For HR', $titles);
    }

    public function test_context_exam_history_only_shows_own_attempts()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $other = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);

        $exam = Exam::create(['title' => 'Test Exam', 'max_violations' => 3, 'created_by' => $admin->id]);
        ExamAttempt::create(['exam_id' => $exam->id, 'user_id' => $pegawai->id, 'status' => 'selesai', 'score' => 85, 'started_at' => now(), 'submitted_at' => now()]);
        ExamAttempt::create(['exam_id' => $exam->id, 'user_id' => $other->id, 'status' => 'selesai', 'score' => 60, 'started_at' => now(), 'submitted_at' => now()]);

        $context = (new ChatbotContextService())->buildContext($pegawai);

        // The "skor" entry for the exam should reflect pegawai's own attempt (85), not other's (60)
        $examEntry = collect($context['exams'])->firstWhere('judul', 'Test Exam');
        $this->assertNotNull($examEntry);
        $this->assertEquals(85, $examEntry['skor']);
    }

    // ── HTTP endpoint ───────────────────────────────────────────────────────

    public function test_chatbot_endpoint_requires_auth()
    {
        $response = $this->postJson(route('chatbot.ask'), ['message' => 'test']);
        $response->assertStatus(401);
    }

    public function test_chatbot_endpoint_validates_message_required()
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        $response = $this->actingAs($user)->postJson(route('chatbot.ask'), []);
        $response->assertStatus(422)->assertJsonValidationErrors(['message']);
    }

    public function test_chatbot_returns_unconfigured_message_when_no_api_key()
    {
        // Ensure config key is blank (default in test env)
        config(['services.anthropic.key' => '']);

        $user = User::factory()->create(['role' => 'pegawai']);
        $response = $this->actingAs($user)->postJson(route('chatbot.ask'), ['message' => 'Halo']);

        $response->assertStatus(200)->assertJsonPath('reply', fn ($v) => str_contains($v, 'belum dikonfigurasi'));
    }
}
