<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RemoveHomepageContentFinished extends Notification
{
    public function __construct(public readonly int $count) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'level'   => 'success',
            'message' => "Queued removal for {$this->count} homepage link(s).",
        ];
    }
}