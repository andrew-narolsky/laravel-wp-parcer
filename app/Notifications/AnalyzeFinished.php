<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AnalyzeFinished extends Notification
{
    public function __construct(
        public readonly int $total,
        public readonly int $alive,
        public readonly int $broken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'level'   => 'success',
            'message' => "Link analysis finished: {$this->total} checked, {$this->alive} alive, {$this->broken} broken.",
        ];
    }
}