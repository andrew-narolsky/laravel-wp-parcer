<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class BackupCreated extends Notification
{
    public function __construct(
        public readonly string $filename,
        public readonly int $sites,
        public readonly int $links,
        public readonly int $projects,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'level'   => 'success',
            'message' => "Backup created: {$this->filename} ({$this->sites} sites, {$this->links} links, {$this->projects} projects).",
            'reload'  => true,
        ];
    }
}