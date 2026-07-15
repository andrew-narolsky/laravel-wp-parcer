<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ImportStarted extends Notification
{
    public function __construct(public readonly string $linkType) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'level'   => 'info',
            'message' => 'Import of ' . ($this->linkType === 'homepage' ? 'homepage' : 'post') . ' links started.',
        ];
    }
}