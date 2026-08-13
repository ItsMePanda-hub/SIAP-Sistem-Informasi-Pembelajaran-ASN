<?php

namespace App\Policies;

use App\Models\Letter;
use App\Models\User;

class LetterPolicy
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

    public function view(User $user, Letter $letter): bool
    {
        return $letter->isAccessibleBy($user);
    }

    public function create(User $user): bool
    {
        return $user->role === 'atasan';
    }

    public function update(User $user, Letter $letter): bool
    {
        return $user->role === 'atasan' && $user->unit_kerja === $letter->target_unit_kerja;
    }

    public function delete(User $user, Letter $letter): bool
    {
        return $this->update($user, $letter);
    }
}
