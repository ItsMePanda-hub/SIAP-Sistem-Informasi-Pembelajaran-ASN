<?php

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ExamAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Exam $exam) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Ujian Tersedia',
            'message' => $this->exam->title,
            'url' => route('exams.show', $this->exam),
            'type' => 'exam.available',
        ];
    }
}
