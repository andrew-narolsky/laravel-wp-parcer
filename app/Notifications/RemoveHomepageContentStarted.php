<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RemoveHomepageContentStarted extends Notification
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
            'message' => "Removing our content from {$this->count} homepage link(s)...",
        ];
    }
}