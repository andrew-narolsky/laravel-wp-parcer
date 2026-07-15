<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\User;
use App\Notifications\RepublishFinished;
use App\Notifications\RepublishStarted;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RepublishUnpublishedLinksJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $type) {}

    public function handle(): void
    {
        $links = Link::where('type', $this->type)
            ->where('status', '!=', 'published')
            ->get();

        Notification::send(User::all(), new RepublishStarted($this->type, $links->count()));

        if ($links->isEmpty()) {
            Notification::send(User::all(), new RepublishFinished($this->type, 0, 0));
            return;
        }

        $ids = $links->pluck('id');

        Link::whereIn('id', $ids)->update(['status' => 'pending', 'failed_reason' => null]);

        Bus::batch($links->map(fn (Link $link) => new PublishLinkJob($link))->all())
            ->name("republish-{$this->type}")
            ->allowFailures()
            ->finally(function (Batch $batch) use ($ids) {
                $published = Link::whereIn('id', $ids)->where('status', 'published')->count();
                $failed    = Link::whereIn('id', $ids)->where('status', 'failed')->count();

                Log::info("Republish unpublished {$this->type} links complete", [
                    'queued'    => $ids->count(),
                    'published' => $published,
                    'failed'    => $failed,
                ]);

                Notification::send(User::all(), new RepublishFinished($this->type, $published, $failed));
            })
            ->dispatch();
    }
}