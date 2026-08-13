<?php

namespace Tests\Feature;

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
        $this->assertArrayHasKey('training_progress', $stats);
        $this->assertArrayHasKey('exam_history', $stats);
    }
}
