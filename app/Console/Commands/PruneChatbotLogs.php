<?php

namespace App\Console\Commands;

use App\Models\ChatbotLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneChatbotLogs extends Command
{
    protected $signature = 'chatbot:prune-logs';

    protected $description = 'Remove chatbot logs that have exceeded the configured retention period.';

    public function handle(): int
    {
        $retentionDays = (int) config('services.chatbot.log_retention_days');
        $deleted = ChatbotLog::where('created_at', '<', now()->subDays($retentionDays))->delete();

        $this->info("Deleted {$deleted} chatbot log(s).");
        Log::info('Chatbot log retention completed.', [
            'deleted_count' => $deleted,
            'retention_days' => $retentionDays,
        ]);

        return self::SUCCESS;
    }
}
