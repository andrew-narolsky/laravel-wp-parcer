<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RepublishFinished extends Notification
{
    public function __construct(
        public readonly string $linkType,
        public readonly int $count,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $type = $this->linkType === 'homepage' ? 'homepage' : 'post';

        return [
            'level'   => 'success',
            'message' => "Queued {$this->count} {$type} link(s) for republishing.",
        ];
    }
}