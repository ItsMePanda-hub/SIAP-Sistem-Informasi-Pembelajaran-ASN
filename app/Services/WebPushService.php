<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function __construct(private ?WebPush $webPush = null) {}

    public function isConfigured(): bool
    {
        return ! empty(config('webpush.public_key')) && ! empty(config('webpush.private_key'));
    }

    public function publicKey(): ?string
    {
        return config('webpush.public_key') ?: null;
    }

    public static function payloadFromDatabase(array $data): array
    {
        return [
            'title' => $data['title'] ?? 'SIAP',
            'body' => $data['message'] ?? '',
            'url' => $data['url'] ?? '/notifications',
            'type' => $data['type'] ?? 'notification',
        ];
    }

    public function sendToUser(User $user, array $payload): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();
        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $webPush = $this->webPush ?? new WebPush([
            'VAPID' => [
                'subject' => config('webpush.subject'),
                'publicKey' => config('webpush.public_key'),
                'privateKey' => config('webpush.private_key'),
            ],
        ]);

        $sent = 0;
        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->p256dh,
                    'authToken' => $sub->auth,
                ]);
                $report = $webPush->sendOneNotification($subscription, json_encode($payload));
                if ($report->isSuccess()) {
                    $sent++;
                } else {
                    $this->handleFailure($sub, (string) $report->getReason());
                }
            } catch (\Throwable $e) {
                Log::warning('WebPush send gagal', ['user_id' => $user->id, 'exception' => class_basename($e)]);
            }
        }

        return $sent;
    }

    private function handleFailure(PushSubscription $sub, string $reason): void
    {
        $stale = str_contains($reason, '410') || str_contains($reason, '404')
            || str_contains(strtolower($reason), 'gone')
            || str_contains(strtolower($reason), 'not found')
            || str_contains(strtolower($reason), 'invalid');

        if ($stale) {
            $sub->delete();
        }

        Log::warning('WebPush delivery gagal', ['subscription_id' => $sub->id, 'reason' => substr($reason, 0, 200)]);
    }
}
