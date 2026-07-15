<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RemovePostsStarted extends Notification
{
    public function __construct(public readonly int $count) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'level'   => 'info',
            'message' => "Removing {$this->count} published post link(s)...",
        ];
    }
}