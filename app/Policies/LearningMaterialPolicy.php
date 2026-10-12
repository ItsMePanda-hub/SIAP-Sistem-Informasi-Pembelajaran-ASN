<?php

namespace App\Policies;

use App\Models\LearningMaterial;
use App\Models\User;

class LearningMaterialPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->role === 'admin') {
            return true;
        }

        return null;
    }

    private function isLibraryManager(User $user): bool
    {
        return in_array($user->role, ['admin', 'admin_library', 'pemilik'], true);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LearningMaterial $material): bool
    {
        if ($material->isCurrent()) {
            return true;
        }

        return $this->isLibraryManager($user);
    }

    public function create(User $user): bool
    {
        return $this->isLibraryManager($user);
    }

    public function update(User $user, LearningMaterial $material): bool
    {
        return $this->isLibraryManager($user);
    }

    public function delete(User $user, LearningMaterial $material): bool
    {
        return $this->isLibraryManager($user);
    }
}
