<?php

namespace App\Notifications;

use App\Models\ExamAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ExamResultAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ExamAttempt $attempt) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Hasil Ujian Tersedia',
            'message' => 'Hasil ujian ' . $this->attempt->exam->title . ' telah tersedia.',
            'url' => route('exams.results', $this->attempt->exam_id),
            'type' => 'exam.result_available',
        ];
    }
}
