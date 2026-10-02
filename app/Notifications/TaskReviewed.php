<?php

namespace App\Notifications;

use App\Models\TaskAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskReviewed extends Notification
{
    use Queueable;

    public function __construct(public TaskAssignment $assignment) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $done = $this->assignment->status === 'selesai';

        return [
            'title' => $done ? 'Tugas Selesai' : 'Tugas Perlu Revisi',
            'message' => 'Tugas ' . $this->assignment->task->title . ($done ? ' telah selesai.' : ' perlu revisi.'),
            'url' => route('tasks.show', $this->assignment->task),
            'type' => 'workspace.task_reviewed',
        ];
    }
}
