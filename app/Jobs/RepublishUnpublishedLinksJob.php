<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\User;
use App\Notifications\RepublishFinished;
use App\Notifications\RepublishStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RepublishUnpublishedLinksJob implements ShouldQueue
{
    use Queueable;

    // A retry would re-select "not published" links and dispatch a second PublishLinkJob
    // for each one already queued by the first attempt.
    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public readonly string $type) {}

    public function handle(): void
    {
        $links = Link::where('type', $this->type)
            ->where('status', '!=', 'published')
            ->get();

        Notification::send(User::all(), new RepublishStarted($this->type, $links->count()));

        foreach ($links as $link) {
            $link->update(['status' => 'pending', 'failed_reason' => null]);
            dispatch(new PublishLinkJob($link));
        }

        Log::info("Republish unpublished {$this->type} links: {$links->count()} queued");

        Notification::send(User::all(), new RepublishFinished($this->type, $links->count()));
    }
}