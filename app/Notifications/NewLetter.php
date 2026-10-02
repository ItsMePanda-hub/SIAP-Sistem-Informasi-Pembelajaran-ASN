<?php

namespace App\Notifications;

use App\Models\Letter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewLetter extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Letter $letter) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Surat Baru',
            'message' => $this->letter->title,
            'url' => route('letters.index'),
            'type' => 'letter.created',
        ];
    }
}
