<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Training;
use App\Models\TrainingProgress;
use App\Models\User;

/**
 * Builds a compact, user-scoped JSON context for the chatbot prompt.
 * ALL queries are filtered through the same visibility rules used by
 * Policies — no extra access is granted here.
 */
class ChatbotContextService
{
    public function buildContext(User $user): array
    {
        return [
            'user' => [
                'name'       => $user->name,
                'role'       => $user->role,
                'unit_kerja' => $user->unit_kerja,
            ],
            'announcements' => $this->getAnnouncements($user),
            'trainings'     => $this->getTrainings($user),
            'exams'         => $this->getExams($user),
            'statistics'    => (new StatisticsService())->getStatsForUser($user),
        ];
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /**
     * Same visibility scope used by AnnouncementController@index
     * and AnnouncementPolicy@view.
     */
    private function getAnnouncements(User $user): array
    {
        $query = Announcement::latest()->limit(10);

        if (! $user->isSystemWide()) {
            $query->where(function ($q) use ($user) {
                $q->where('target_unit_kerja', $user->unit_kerja)
                  ->orWhereNull('target_unit_kerja');
            });
        }

        return $query->get()->map(fn ($a) => [
            'title'           => $a->title,
            'category'        => $a->category,
            'sudah_dibaca'    => $a->isReadBy($user),
            'tanggal'         => $a->created_at->toDateString(),
        ])->toArray();
    }

    /**
     * Same visibility scope used by TrainingController@index
     * and TrainingPolicy@view.
     */
    private function getTrainings(User $user): array
    {
        $query = Training::latest()->limit(10);

        if (! $user->isSystemWide()) {
            $query->where(function ($q) use ($user) {
                $q->where('target_unit_kerja', $user->unit_kerja)
                  ->orWhereNull('target_unit_kerja');
            });
        }

        return $query->get()->map(function ($t) use ($user) {
            $progress = $t->progressFor($user);
            return [
                'judul'     => $t->title,
                'status'    => $progress?->status ?? 'belum_dimulai',
                'sertifikat'=> $progress?->certificate_code,
            ];
        })->toArray();
    }

    /**
     * Same visibility scope used by ExamController@index
     * and ExamPolicy@view.
     */
    private function getExams(User $user): array
    {
        $query = Exam::latest()->limit(10);

        if (! $user->isSystemWide()) {
            $query->where(function ($q) use ($user) {
                $q->where('target_unit_kerja', $user->unit_kerja)
                  ->orWhereNull('target_unit_kerja');
            });
        }

        return $query->get()->map(function ($e) use ($user) {
            $attempt = $e->attemptFor($user);
            return [
                'judul'  => $e->title,
                'status' => $attempt?->status ?? 'belum_dikerjakan',
                'skor'   => $attempt?->score,
            ];
        })->toArray();
    }
}
