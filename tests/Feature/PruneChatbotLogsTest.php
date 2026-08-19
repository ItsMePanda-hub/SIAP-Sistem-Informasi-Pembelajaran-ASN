<?php

namespace Tests\Feature;

use App\Models\ChatbotLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PruneChatbotLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_removes_only_logs_outside_the_retention_period(): void
    {
        config(['services.chatbot.log_retention_days' => 90]);
        $user = User::factory()->create(['role' => 'pegawai']);
        $expiredLog = ChatbotLog::create([
            'user_id' => $user->id,
            'message' => 'Pertanyaan lama',
            'reply' => 'Jawaban lama',
        ]);
        $expiredLog->forceFill(['created_at' => now()->subDays(91)])->save();
        $retainedLog = ChatbotLog::create([
            'user_id' => $user->id,
            'message' => 'Pertanyaan baru',
            'reply' => 'Jawaban baru',
        ]);
        $retainedLog->forceFill(['created_at' => now()->subDays(89)])->save();

        $exitCode = Artisan::call('chatbot:prune-logs');

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseMissing('chatbot_logs', ['id' => $expiredLog->id]);
        $this->assertDatabaseHas('chatbot_logs', ['id' => $retainedLog->id]);
    }
}
