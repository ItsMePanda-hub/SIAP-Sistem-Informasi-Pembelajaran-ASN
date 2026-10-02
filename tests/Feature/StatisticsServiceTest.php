<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_atasan_only_gets_stats_for_own_unit_kerja()
    {
        $atasan = User::factory()->create(['role' => 'atasan', 'unit_kerja' => 'IT']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'HR']);
        User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);

        $service = new StatisticsService();
        $stats = $service->getStatsForUser($atasan);

        $this->assertEquals('manager', $stats['type']);
        $this->assertCount(1, $stats['unit_stats']);
        $this->assertEquals('IT', $stats['unit_stats'][0]['unit_kerja']);
    }

    public function test_pengguna_only_gets_own_stats()
    {
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);

        $service = new StatisticsService();
        $stats = $service->getStatsForUser($pegawai);

        $this->assertEquals('pegawai', $stats['type']);
        $this->assertArrayHasKey('unread_announcements', $stats);
        $this->assertArrayHasKey('task_completion_percent', $stats);
        $this->assertArrayHasKey('exam_history', $stats);
    }

    public function test_task_completion_percent_75_with_4_assignments()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);

        for ($i = 0; $i < 4; $i++) {
            $task = Task::create([
                'title' => "Task $i",
                'description' => 'desc',
                'deadline' => now()->addDays(2),
                'target_type' => 'individu',
                'created_by' => $admin->id,
            ]);
            TaskAssignment::create([
                'task_id' => $task->id,
                'user_id' => $pegawai->id,
                'status' => $i < 3 ? 'selesai' : 'belum_dikerjakan',
            ]);
        }

        $service = new StatisticsService();
        $stats = $service->getStatsForUser($pegawai);
        $this->assertEquals(75, $stats['task_completion_percent']);

        $roster = $service->getTeamOverviewForUser($admin);
        $entry = collect($roster)->firstWhere('name', $pegawai->name);
        $this->assertNotNull($entry);
        $this->assertEquals(75, $entry['task_completion_percent']);
    }

    public function test_task_completion_percent_zero_without_assignments()
    {
        $pegawai = User::factory()->create(['role' => 'pegawai', 'unit_kerja' => 'IT']);
        $admin = User::factory()->create(['role' => 'admin']);

        $service = new StatisticsService();
        $stats = $service->getStatsForUser($pegawai);
        $this->assertEquals(0, $stats['task_completion_percent']);

        $roster = $service->getTeamOverviewForUser($admin);
        $entry = collect($roster)->firstWhere('name', $pegawai->name);
        $this->assertNotNull($entry);
        $this->assertEquals(0, $entry['task_completion_percent']);
    }
}
