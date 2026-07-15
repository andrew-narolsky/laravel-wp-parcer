<?php

namespace App\Notifications;

use App\Models\Link;
use Illuminate\Notifications\Notification;

class LinkPublishFinished extends Notification
{
    public function __construct(
        public readonly Link $link,
        public readonly bool $success,
        public readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->success
            ? [
                'level'   => 'success',
                'message' => "Link \"{$this->link->anchor}\" published: {$this->link->wp_url}",
            ]
            : [
                'level'   => 'error',
                'message' => "Link \"{$this->link->anchor}\" failed to publish: {$this->reason}",
            ];
    }
}