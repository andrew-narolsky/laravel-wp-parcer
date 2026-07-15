<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class BackupFailed extends Notification
{
    public function __construct(
        public readonly string $action,
        public readonly string $error,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'level'   => 'error',
            'message' => "Backup {$this->action} failed: {$this->error}",
        ];
    }
}