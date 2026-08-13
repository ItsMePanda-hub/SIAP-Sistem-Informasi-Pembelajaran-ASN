<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if (in_array($user->role, ['admin', 'pemilik'])) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Exam $exam): bool
    {
        if (is_null($exam->target_unit_kerja)) {
            return true;
        }
        return $user->unit_kerja === $exam->target_unit_kerja;
    }

    public function create(User $user): bool
    {
        return $user->role === 'atasan';
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->role === 'atasan' && $user->unit_kerja === $exam->target_unit_kerja;
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }
}
