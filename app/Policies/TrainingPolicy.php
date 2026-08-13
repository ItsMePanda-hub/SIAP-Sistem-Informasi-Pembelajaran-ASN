<?php

namespace App\Policies;

use App\Models\Training;
use App\Models\User;

class TrainingPolicy
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

    public function view(User $user, Training $training): bool
    {
        if (is_null($training->target_unit_kerja)) {
            return true;
        }
        return $user->unit_kerja === $training->target_unit_kerja;
    }

    public function create(User $user): bool
    {
        return $user->role === 'atasan';
    }

    public function update(User $user, Training $training): bool
    {
        return $user->role === 'atasan' && $user->unit_kerja === $training->target_unit_kerja;
    }

    public function delete(User $user, Training $training): bool
    {
        return $this->update($user, $training);
    }
}
