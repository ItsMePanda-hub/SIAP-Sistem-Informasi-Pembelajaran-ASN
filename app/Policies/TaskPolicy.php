<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->role === 'admin') {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'atasan']);
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->role === 'pemilik') {
            return true;
        }

        if ($user->role === 'atasan') {
            return $task->created_by === $user->id || $task->target_unit_kerja === $user->unit_kerja;
        }

        if ($user->role === 'pegawai') {
            return $task->assignments()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public function review(User $user, TaskAssignment $assignment): bool
    {
        if ($user->role === 'atasan') {
            return $user->unit_kerja === $assignment->user->unit_kerja;
        }

        return false;
    }

    public function submit(User $user, TaskAssignment $assignment): bool
    {
        return $assignment->user_id === $user->id;
    }
}
