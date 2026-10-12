<?php

namespace App\Services;

use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class LibraryRecommendationService
{
    public function getRecommendations(User $user, int $limit = 6): Collection
    {
        $isManager = in_array($user->role, ['admin', 'admin_library', 'pemilik'], true);

        $progressMap = ReadingProgress::where('user_id', $user->id)
            ->pluck('progress_percent', 'learning_material_id')
            ->all();

        $completedIds = collect($progressMap)->filter(fn ($v) => (int) $v >= 100)->keys()->all();

        $testedCategoryIds = [];
        $nextLevelId = null;

        if (! empty($progressMap)) {
            $progressMaterialIds = array_keys($progressMap);
            $studied = LearningMaterial::whereIn('id', $progressMaterialIds)
                ->get(['id', 'learning_category_id', 'learning_level_id']);

            $testedCategoryIds = $studied->pluck('learning_category_id')->filter()->unique()->values()->all();

            $studiedLevelIds = $studied->pluck('learning_level_id')->filter()->unique()->all();
            if (! empty($studiedLevelIds)) {
                $levels = LearningLevel::whereIn('id', $studiedLevelIds)->get(['id', 'sort_order']);
                $maxOrder = $levels->max('sort_order');
                if (! is_null($maxOrder)) {
                    $next = LearningLevel::where('sort_order', $maxOrder + 1)->first();
                    $nextLevelId = $next?->id;
                }
            }
        }

        $q = LearningMaterial::with(['level', 'category']);

        if (! $isManager) {
            $q->current();
        }

        if (! empty($completedIds)) {
            $q->whereNotIn('id', $completedIds);
        }

        $candidates = $q->orderByDesc('created_at')->orderByDesc('id')->limit(50)->get();

        $scored = $candidates->map(function (LearningMaterial $m) use ($progressMap, $testedCategoryIds, $nextLevelId) {
            $pp = $progressMap[$m->id] ?? null;
            $isContinue = ! is_null($pp) && (int) $pp > 0 && (int) $pp < 100;
            $isCategory = in_array($m->learning_category_id, $testedCategoryIds, true);
            $isNextLevel = ! is_null($nextLevelId) && (int) $m->learning_level_id === (int) $nextLevelId;

            if ($isContinue) {
                $reason = 'Lanjutkan materi yang belum selesai.';
            } elseif ($isCategory) {
                $reason = 'Sesuai kategori yang pernah Anda pelajari.';
            } elseif ($isNextLevel) {
                $reason = 'Materi pada level pembelajaran berikutnya.';
            } else {
                $reason = 'Materi terbaru yang tersedia.';
            }

            return [
                'material' => $m,
                'reason' => $reason,
                'isContinue' => $isContinue,
                'isCategory' => $isCategory,
                'isNextLevel' => $isNextLevel,
                'progress' => $pp,
            ];
        });

        $sorted = $scored->sortBy([
            fn ($a, $b) => ($a['isContinue'] ? 0 : 1) <=> ($b['isContinue'] ? 0 : 1),
            fn ($a, $b) => ($a['isCategory'] ? 0 : 1) <=> ($b['isCategory'] ? 0 : 1),
            fn ($a, $b) => ($a['isNextLevel'] ? 0 : 1) <=> ($b['isNextLevel'] ? 0 : 1),
            fn ($a, $b) => $b['material']->created_at <=> $a['material']->created_at,
            fn ($a, $b) => $b['material']->id <=> $a['material']->id,
        ])->values()->take($limit);

        return $sorted;
    }
}
