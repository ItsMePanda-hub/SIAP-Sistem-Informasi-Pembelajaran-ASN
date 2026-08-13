<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\ExamAttempt;
use App\Models\TrainingProgress;
use App\Models\Training;
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

            // Training Compliance
            $unitTrainings = Training::whereNull('target_unit_kerja')->orWhere('target_unit_kerja', $unit)->count();
            $divisorTrainings = $unitTrainings ?: 1;
            $trainingCompletions = TrainingProgress::whereIn('user_id', $pegawaiIds)->where('status', 'selesai')->count();
            $trainingCompliance = min(100, ($trainingCompletions / ($divisorTrainings * $totalPegawai)) * 100);

            // Exam Score
            $avgExamScore = ExamAttempt::whereIn('user_id', $pegawaiIds)->whereNotNull('score')->avg('score') ?? 0;

            $stats[] = [
                'unit_kerja' => $unit,
                'avg_read_rate' => round($readRate, 2),
                'avg_training_compliance' => round($trainingCompliance, 2),
                'avg_exam_score' => round($avgExamScore, 2),
            ];
        }

        return [
            'type' => 'manager',
            'total_announcements' => $targetAnnouncements,
            'unit_stats' => $stats,
        ];
    }

    private function getPegawaiStats(User $user)
    {
        // Unread Announcements
        $totalAnnouncements = Announcement::whereNull('target_unit_kerja')
            ->orWhere('target_unit_kerja', $user->unit_kerja)
            ->count();
        $readAnnouncements = AnnouncementRead::where('user_id', $user->id)->count();
        $unreadAnnouncements = max(0, $totalAnnouncements - $readAnnouncements);

        // Training Progress
        $completedTrainings = TrainingProgress::where('user_id', $user->id)->where('status', 'selesai')->count();
        $totalTrainings = Training::whereNull('target_unit_kerja')
            ->orWhere('target_unit_kerja', $user->unit_kerja)
            ->count();
        $trainingProgress = $totalTrainings > 0 ? round(($completedTrainings / $totalTrainings) * 100, 2) : 100;

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
            'training_progress' => $trainingProgress,
            'exam_history' => $examHistory,
        ];
    }
}
