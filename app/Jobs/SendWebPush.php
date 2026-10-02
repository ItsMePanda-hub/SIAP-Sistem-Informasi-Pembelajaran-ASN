<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWebPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId, public array $payload) {}

    public function handle(WebPushService $service): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        try {
            $service->sendToUser($user, $this->payload);
        } catch (\Throwable $e) {
        }
    }
}
