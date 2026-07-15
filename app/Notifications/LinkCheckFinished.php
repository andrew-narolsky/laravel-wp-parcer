<?php

namespace App\Notifications;

use App\Models\Link;
use Illuminate\Notifications\Notification;

class LinkCheckFinished extends Notification
{
    public function __construct(public readonly Link $link, public readonly string $status) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'level'   => $this->status === 'alive' ? 'success' : 'error',
            'message' => "Link \"{$this->link->anchor}\" check finished: {$this->status}",
        ];
    }
}