<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Notifications\Notification;

class ReplaceHomepageContentFinished extends Notification
{
    public function __construct(
        public readonly Site $site,
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
                'message' => "Homepage content replaced on \"{$this->site->name}\".",
            ]
            : [
                'level'   => 'error',
                'message' => "Failed to replace homepage content on \"{$this->site->name}\": {$this->reason}",
            ];
    }
}
