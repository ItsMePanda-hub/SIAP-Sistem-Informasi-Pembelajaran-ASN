<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\ExamAvailable;
use App\Notifications\ExamResultAvailable;
use App\Notifications\NewAnnouncement;
use App\Notifications\NewLetter;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskReviewed;
use App\Notifications\TaskSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_notification_classes_implement_should_queue(): void
    {
        $classes = [
            NewAnnouncement::class,
            NewLetter::class,
            TaskAssigned::class,
            TaskSubmitted::class,
            TaskReviewed::class,
            ExamAvailable::class,
            ExamResultAvailable::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(
                is_subclass_of($class, ShouldQueue::class),
                "$class harus implement ShouldQueue"
            );
            $notif = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
            $this->assertEquals(['database'], $notif->via(new User()));
        }
    }

    public function test_notification_payload_unchanged_when_queued(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ann = Announcement::create(['title' => 'Payload Check', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => null, 'created_by' => $admin->id]);
        $notif = new NewAnnouncement($ann);
        $data = $notif->toDatabase($admin);
        $this->assertEquals('Pengumuman Baru', $data['title']);
        $this->assertEquals('Payload Check', $data['message']);
        $this->assertStringContainsString('/pengumuman/', $data['url']);
        $this->assertEquals('announcement.created', $data['type']);
    }

    public function test_dispatch_is_queued_not_immediate_when_fake(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);

        $this->actingAs($admin)->post(route('announcements.store'), [
            'title' => 'Queued A', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'IT',
        ]);

        $this->assertDatabaseCount('notifications', 0);
        Queue::assertPushed(SendQueuedNotifications::class, function ($job) {
            return true;
        });
    }

    public function test_worker_processes_queue_and_notification_available(): void
    {
        config(['queue.default' => 'database']);

        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);

        $this->actingAs($admin)->post(route('announcements.store'), [
            'title' => 'Worker Test', 'body' => 'B', 'category' => 'rutin', 'target_unit_kerja' => 'IT',
        ]);

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertEquals(0, $pegawai->fresh()->notifications()->count());

        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--sleep' => 0,
        ]);

        $this->assertDatabaseCount('jobs', 0);
        $this->assertEquals(1, $pegawai->fresh()->notifications()->count());
        $this->assertEquals(1, $pegawai->fresh()->unreadNotifications()->count());
        $data = $pegawai->fresh()->notifications()->first()->data;
        $this->assertEquals('announcement.created', $data['type']);
        $this->assertEquals('Worker Test', $data['message']);
        $this->assertStringContainsString('/pengumuman/', $data['url']);
    }
}
