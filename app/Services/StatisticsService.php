<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\ExamAttempt;
use App\Models\TaskAssignment;
use App\Models\User;

class StatisticsService
{
    public function getStatsForUser(User $user)
    {
        if (in_array($user->role, ['admin', 'pemilik'])) {
            return $this->getManagerStats();
        } elseif ($user->role === 'atasan') {
            return $this->getManagerStats($user->unit_kerja);
        } else {
            return $this->getPegawaiStats($user);
        }
    }

    private function getManagerStats($unitKerja = null)
    {
        $units = $unitKerja 
            ? [$unitKerja] 
            : User::whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja')->toArray();
        
        $stats = [];
        $totalAnnouncementsOverall = Announcement::count();
        $targetAnnouncements = $unitKerja 
            ? Announcement::whereNull('target_unit_kerja')->orWhere('target_unit_kerja', $unitKerja)->count() 
            : $totalAnnouncementsOverall;

        foreach ($units as $unit) {
            $pegawaiIds = User::whereIn('role', ['pegawai', 'pengguna'])->where('unit_kerja', $unit)->pluck('id');
            $totalPegawai = $pegawaiIds->count() ?: 1;

            // Read Rate
            $unitAnnouncements = Announcement::whereNull('target_unit_kerja')->orWhere('target_unit_kerja', $unit)->count();
            $divisorAnnouncements = $unitAnnouncements ?: 1;
            $reads = AnnouncementRead::whereIn('user_id', $pegawaiIds)->count();
            $readRate = min(100, ($reads / ($divisorAnnouncements * $totalPegawai)) * 100);

            $taskPercents = [];
            foreach ($pegawaiIds as $pegawaiId) {
                $totalTa = TaskAssignment::where('user_id', $pegawaiId)->count();
                $doneTa = TaskAssignment::where('user_id', $pegawaiId)->where('status', 'selesai')->count();
                $taskPercents[] = $totalTa > 0 ? (int) round(($doneTa / $totalTa) * 100) : 0;
            }
            $avgTaskCompletion = count($taskPercents) > 0 ? round(array_sum($taskPercents) / count($taskPercents), 2) : 0;

            // Exam Score
            $avgExamScore = ExamAttempt::whereIn('user_id', $pegawaiIds)->whereNotNull('score')->avg('score') ?? 0;

            $stats[] = [
                'unit_kerja' => $unit,
                'avg_read_rate' => round($readRate, 2),
                'avg_task_completion' => $avgTaskCompletion,
                'avg_exam_score' => round($avgExamScore, 2),
            ];
        }

        return [
            'type' => 'manager',
            'total_announcements' => $targetAnnouncements,
            'unit_stats' => $stats,
        ];
    }

    public function getTeamOverviewForUser(User $user): array
    {
        // Pegawai tidak boleh mendapat data pegawai lain sama sekali
        if ($user->role === 'pegawai' || $user->role === 'pengguna') {
            return [];
        }

        // Tentukan roster berdasarkan role
        if ($user->role === 'atasan') {
            // Atasan hanya boleh melihat pegawai di unit_kerja yang sama
            $query = User::with('atasan')
                ->whereIn('role', ['pegawai', 'pengguna'])
                ->where('unit_kerja', $user->unit_kerja);
        } else {
            // admin / pemilik: melihat atasan dan pegawai lintas unit
            $query = User::with('atasan')
                ->whereIn('role', ['atasan', 'pegawai', 'pengguna']);
        }

        $pegawaiList = $query->get();

        $roster = [];
        foreach ($pegawaiList as $p) {
            // exam_completed: true jika ada setidaknya 1 ExamAttempt dengan score tidak null
            $examCompleted = ExamAttempt::where('user_id', $p->id)
                ->whereNotNull('score')
                ->exists();

            $totalTa = TaskAssignment::where('user_id', $p->id)->count();
            $doneTa = TaskAssignment::where('user_id', $p->id)->where('status', 'selesai')->count();
            $taskCompletionPercent = $totalTa > 0 ? (int) round(($doneTa / $totalTa) * 100) : 0;

            // announcement_unread_count
            $totalAnnouncements = Announcement::whereNull('target_unit_kerja')
                ->orWhere('target_unit_kerja', $p->unit_kerja)
                ->count();
            $readCount = AnnouncementRead::where('user_id', $p->id)->count();
            $unreadCount = max(0, $totalAnnouncements - $readCount);

            // HANYA field aman — TIDAK PERNAH menyertakan email, nip, password, dst.
            $roster[] = [
                'name'                    => $p->name,
                'unit_kerja'              => $p->unit_kerja,
                'role'                    => $p->role,
                'atasan_name'             => $p->atasan?->name,
                'exam_completed'          => $examCompleted,
                'task_completion_percent' => $taskCompletionPercent,
                'announcement_unread_count' => $unreadCount,
            ];
        }

        return $roster;
    }

    private function getPegawaiStats(User $user)
    {
        // Unread Announcements
        $totalAnnouncements = Announcement::whereNull('target_unit_kerja')
            ->orWhere('target_unit_kerja', $user->unit_kerja)
            ->count();
        $readAnnouncements = AnnouncementRead::where('user_id', $user->id)->count();
        $unreadAnnouncements = max(0, $totalAnnouncements - $readAnnouncements);

        $totalTa = TaskAssignment::where('user_id', $user->id)->count();
        $doneTa = TaskAssignment::where('user_id', $user->id)->where('status', 'selesai')->count();
        $taskCompletionPercent = $totalTa > 0 ? (int) round(($doneTa / $totalTa) * 100) : 0;

        // Exam History
        $examHistory = ExamAttempt::where('user_id', $user->id)
            ->whereNotNull('score')
            ->with('exam')
            ->latest()
            ->take(5)
            ->get();

        return [
            'type' => 'pegawai',
            'unread_announcements' => $unreadAnnouncements,
            'task_completion_percent' => $taskCompletionPercent,
            'exam_history' => $examHistory,
        ];
    }
}
