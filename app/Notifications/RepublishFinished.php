<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RepublishFinished extends Notification
{
    public function __construct(
        public readonly string $linkType,
        public readonly int $published,
        public readonly int $failed,
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
            'message' => "Republishing {$type} links finished: {$this->published} published, {$this->failed} failed.",
        ];
    }
}