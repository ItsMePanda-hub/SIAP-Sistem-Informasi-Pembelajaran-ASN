<?php

namespace App\Notifications;

use App\Models\TaskAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TaskAssignment $assignment) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Tugas Dikumpulkan',
            'message' => $this->assignment->user->name . ' mengumpulkan tugas: ' . $this->assignment->task->title,
            'url' => route('tasks.show', $this->assignment->task),
            'type' => 'workspace.task_submitted',
        ];
    }
}
