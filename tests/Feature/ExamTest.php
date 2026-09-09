<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ExamOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    public function test_anti_cheat_tab_switch_increments_violation_and_auto_submits()
    {
        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $exam = Exam::create([
            'title' => 'Test Exam',
            'max_violations' => 2,
            'created_by' => $pegawai->id, // just for foreign key
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $pegawai->id,
            'status' => 'sedang_berjalan',
            'started_at' => now(),
        ]);

        // First violation
        $response = $this->actingAs($pegawai)->postJson(route('exams.violation', $exam));
        $response->assertJson([
            'violation_count' => 1,
            'is_final' => false,
        ]);
        $this->assertEquals('sedang_berjalan', $attempt->fresh()->status);

        // Second violation (max reached)
        $response = $this->actingAs($pegawai)->postJson(route('exams.violation', $exam));
        $response->assertJson([
            'violation_count' => 2,
            'is_final' => true,
        ]);
        $this->assertEquals('selesai_pelanggaran', $attempt->fresh()->status);
    }

    public function test_mc_auto_graded_correct_and_essay_needs_manual_grading()
    {
        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $admin = User::factory()->create(['role' => 'admin']);
        $exam = Exam::create([
            'title' => 'Test Exam 2',
            'max_violations' => 3,
            'created_by' => $admin->id,
        ]);

        $mcQuestion = ExamQuestion::create(['exam_id' => $exam->id, 'type' => 'pilihan_ganda', 'question' => 'Q1', 'order' => 1]);
        $mcCorrectOption = ExamOption::create(['exam_question_id' => $mcQuestion->id, 'option_text' => 'Benar', 'is_correct' => true]);
        
        $essayQuestion = ExamQuestion::create(['exam_id' => $exam->id, 'type' => 'esai', 'question' => 'Q2', 'order' => 2]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $pegawai->id,
            'status' => 'sedang_berjalan',
            'started_at' => now(),
        ]);

        // Answer MC correctly
        $this->actingAs($pegawai)->postJson(route('exams.answer', $exam), [
            'exam_question_id' => $mcQuestion->id,
            'exam_option_id' => $mcCorrectOption->id,
        ]);

        // Answer Essay
        $this->actingAs($pegawai)->postJson(route('exams.answer', $exam), [
            'exam_question_id' => $essayQuestion->id,
            'essay_answer' => 'Jawaban esai',
        ]);

        // Submit
        $this->actingAs($pegawai)->post(route('exams.submit', $exam));

        $attempt->refresh();
        $this->assertEquals('menunggu_penilaian_esai', $attempt->status);
        $this->assertEquals(50.00, (float) $attempt->score); // MC is 1, Essay ungraded (0) = 50%
        
        // Manual grade essay
        $essayAnswer = $attempt->answers()->where('exam_question_id', $essayQuestion->id)->first();
        $this->assertNull($essayAnswer->essay_graded_correct);

        $this->actingAs($admin)->post(route('exams.grade-essay', $essayAnswer), [
            'is_correct' => true,
        ]);

        $essayAnswer->refresh();
        $attempt->refresh();
        $this->assertTrue((bool) $essayAnswer->essay_graded_correct);
        $this->assertEquals('selesai', $attempt->status);
        $this->assertEquals(100.00, (float) $attempt->score);
    }

    public function test_appeal_violation_flow()
    {
        $pegawai1 = User::factory()->create(['role' => 'pegawai']);
        $pegawai2 = User::factory()->create(['role' => 'pegawai']);
        $admin = User::factory()->create(['role' => 'admin']);

        $exam = Exam::create([
            'title' => 'Appeal Test',
            'max_violations' => 1,
            'created_by' => $admin->id,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $pegawai1->id,
            'status' => 'selesai_pelanggaran',
            'started_at' => now(),
        ]);

        // assert pegawai lain tidak bisa ajukan banding
        $this->actingAs($pegawai2)
             ->post(route('exams.appeal', $exam), ['note' => 'Bukan ujian saya'])
             ->assertForbidden();

        // assert pegawai bisa ajukan banding
        $this->actingAs($pegawai1)
             ->post(route('exams.appeal', $exam), ['note' => 'Listrik mati'])
             ->assertRedirect();
        
        $attempt->refresh();
        $this->assertEquals('diajukan', $attempt->violation_appeal_status);

        // admin tolak banding
        $this->actingAs($admin)
             ->post(route('exams.resolve-appeal', $attempt), ['action' => 'tolak'])
             ->assertRedirect();
        
        $attempt->refresh();
        $this->assertEquals('ditolak', $attempt->violation_appeal_status);
        $this->assertEquals('selesai_pelanggaran', $attempt->status);

        // pegawai ajukan lagi tidak bisa karena status bukan null
        $this->actingAs($pegawai1)
             ->post(route('exams.appeal', $exam), ['note' => 'Tolong dong'])
             ->assertForbidden();

        // set back to diajukan for test 'terima'
        $attempt->update(['violation_appeal_status' => 'diajukan']);
        
        // admin terima banding
        $this->actingAs($admin)
             ->post(route('exams.resolve-appeal', $attempt), ['action' => 'terima'])
             ->assertRedirect();
             
        $attempt->refresh();
        $this->assertEquals('diterima', $attempt->violation_appeal_status);
        $this->assertEquals('sedang_berjalan', $attempt->status);
        $this->assertEquals(0, $attempt->violation_count);
        $this->assertNull($attempt->score);
        
        // pegawai mengerjakan ulang
        $this->actingAs($pegawai1)
             ->post(route('exams.start', $exam))
             ->assertRedirect(route('exams.take', $exam));
    }
}
