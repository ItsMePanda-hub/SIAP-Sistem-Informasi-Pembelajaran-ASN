<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_config_uses_mysql_and_backups_disk(): void
    {
        $this->assertContains('mysql', config('backup.backup.source.databases'));
        $this->assertContains('backups', config('backup.backup.destination.disks'));
        $this->assertEquals(storage_path('app/backups'), config('filesystems.disks.backups.root'));
    }

    public function test_backup_run_and_clean_are_scheduled_daily(): void
    {
        $schedule = new Schedule($this->app);
        $kernel = $this->app->make(\App\Console\Kernel::class);
        $method = new \ReflectionMethod($kernel, 'schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        $events = collect($schedule->events());
        $hasRun = $events->contains(fn ($e) => str_contains((string) $e->command, 'backup:run') && $e->expression === '0 0 * * *');
        $hasClean = $events->contains(fn ($e) => str_contains((string) $e->command, 'backup:clean') && $e->expression === '0 0 * * *');

        $this->assertTrue($hasRun, 'backup:run not scheduled daily');
        $this->assertTrue($hasClean, 'backup:clean not scheduled daily');
    }
}
