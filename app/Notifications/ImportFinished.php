<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ImportFinished extends Notification
{
    public function __construct(
        public readonly string $linkType,
        public readonly int $imported,
        public readonly int $skipped,
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
            'message' => "Import of {$type} links finished: {$this->imported} imported, {$this->skipped} skipped.",
        ];
    }
}