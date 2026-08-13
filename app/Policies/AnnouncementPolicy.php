<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
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
        return true; // handled in controller via query scoping
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if (is_null($announcement->target_unit_kerja)) {
            return true;
        }
        return $user->unit_kerja === $announcement->target_unit_kerja;
    }

    public function create(User $user): bool
    {
        return $user->role === 'atasan';
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->role === 'atasan' && $user->unit_kerja === $announcement->target_unit_kerja;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }
}
