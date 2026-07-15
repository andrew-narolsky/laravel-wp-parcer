<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AnalyzeStarted extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'level'   => 'info',
            'message' => 'Link analysis started.',
        ];
    }
}