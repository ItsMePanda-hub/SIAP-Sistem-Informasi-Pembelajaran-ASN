<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

    private array $vapid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vapid = [
            'public' => str_repeat('A', 86),
            'private' => str_repeat('B', 43),
        ];
        Config::set('webpush.public_key', $this->vapid['public']);
        Config::set('webpush.private_key', $this->vapid['private']);
        Config::set('webpush.subject', 'mailto:test@siap.test');
    }

    public function test_authenticated_can_get_vapid_public_key_and_private_not_exposed(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        $res = $this->actingAs($user)->getJson(route('push.vapid'));
        $res->assertOk()->assertJson(['key' => $this->vapid['public']]);
        $body = $res->getContent();
        $this->assertStringNotContainsString($this->vapid['private'], $body);
        $this->assertStringNotContainsString('private', strtolower($body));
    }

    public function test_guest_cannot_subscribe(): void
    {
        $this->postJson(route('push.subscribe'), [
            'endpoint' => 'https://example.com/push/1',
            'keys' => ['p256dh' => 'k1', 'auth' => 'a1'],
        ])->assertStatus(401);
    }

    public function test_authenticated_can_subscribe_and_guest_subscribe_fails(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($user)->postJson(route('push.subscribe'), [
            'endpoint' => 'https://example.com/push/xyz',
            'keys' => ['p256dh' => 'p256dh-value', 'auth' => 'auth-value'],
        ])->assertOk()->assertJsonStructure(['id']);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint_hash' => hash('sha256', 'https://example.com/push/xyz'),
        ]);
    }

    public function test_subscribe_same_endpoint_idempotent(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        $payload = ['endpoint' => 'https://example.com/push/dup', 'keys' => ['p256dh' => 'k1', 'auth' => 'a1']];
        $this->actingAs($user)->postJson(route('push.subscribe'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('push.subscribe'), ['endpoint' => 'https://example.com/push/dup', 'keys' => ['p256dh' => 'k2', 'auth' => 'a2']])->assertOk();
        $this->assertEquals(1, PushSubscription::where('user_id', $user->id)->count());
        $this->assertEquals('k2', PushSubscription::where('user_id', $user->id)->first()->p256dh);
    }

    public function test_user_cannot_unsubscribe_others_subscription(): void
    {
        $userA = User::factory()->create(['role' => 'pegawai']);
        $userB = User::factory()->create(['role' => 'pegawai']);
        PushSubscription::create([
            'user_id' => $userA->id,
            'endpoint' => 'https://example.com/push/A',
            'endpoint_hash' => hash('sha256', 'https://example.com/push/A'),
            'p256dh' => 'kA',
            'auth' => 'aA',
        ]);

        $this->actingAs($userB)->postJson(route('push.unsubscribe'), [
            'endpoint' => 'https://example.com/push/A',
        ])->assertOk();

        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $userA->id, 'endpoint' => 'https://example.com/push/A']);
    }

    public function test_unsubscribe_own_subscription(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://example.com/push/own',
            'endpoint_hash' => hash('sha256', 'https://example.com/push/own'),
            'p256dh' => 'k',
            'auth' => 'a',
        ]);

        $this->actingAs($user)->postJson(route('push.unsubscribe'), [
            'endpoint' => 'https://example.com/push/own',
        ])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['user_id' => $user->id]);
    }

    public function test_service_payload_and_no_exception_when_configured(): void
    {
        $payload = WebPushService::payloadFromDatabase(['title' => 'T', 'message' => 'M', 'url' => '/x', 'type' => 't']);
        $this->assertEquals('T', $payload['title']);
        $this->assertEquals('M', $payload['body']);
        $this->assertEquals('/x', $payload['url']);

        $service = new WebPushService();
        $this->assertTrue($service->isConfigured());
        $this->assertEquals($this->vapid['public'], $service->publicKey());
    }

    public function test_payload_url_forwarded(): void
    {
        $payload = WebPushService::payloadFromDatabase([
            'title' => 'Ujian Tersedia',
            'message' => 'Ujian IT',
            'url' => 'http://localhost/ujian/5',
            'type' => 'exam.available',
        ]);
        $this->assertStringContainsString('/ujian/5', $payload['url']);
    }

    public function test_stale_subscription_deleted_on_410(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        $sub = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://example.com/push/stale',
            'endpoint_hash' => hash('sha256', 'https://example.com/push/stale'),
            'p256dh' => 'k',
            'auth' => 'a',
        ]);

        $mockWebPush = $this->createMock(WebPush::class);
        $mockWebPush->method('sendOneNotification')->willReturn(new MessageSentReport(
            new Request('POST', $sub->endpoint),
            new Response(410),
            false,
            '410 Gone'
        ));

        $service = new WebPushService($mockWebPush);
        $service->sendToUser($user, ['title' => 'T', 'body' => 'B', 'url' => '/notifications']);

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $sub->id]);
    }

    public function test_vapid_private_never_in_response_or_log_assertion(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        $responses = [
            $this->actingAs($user)->getJson(route('push.vapid'))->getContent(),
            $this->actingAs($user)->postJson(route('push.subscribe'), [
                'endpoint' => 'https://example.com/push/privcheck',
                'keys' => ['p256dh' => 'k', 'auth' => 'a'],
            ])->getContent(),
        ];

        foreach ($responses as $body) {
            $this->assertStringNotContainsString($this->vapid['private'], $body);
        }
    }

    public function test_database_notification_still_created_even_without_push_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);

        $this->actingAs($admin)->post(route('announcements.store'), [
            'title' => 'No Push Sub', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'IT',
        ]);

        $this->assertEquals(1, $pegawai->notifications()->count());
        $this->assertEquals(0, PushSubscription::where('user_id', $pegawai->id)->count());
    }
}
