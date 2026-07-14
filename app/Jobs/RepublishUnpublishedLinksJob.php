<?php

namespace App\Jobs;

use App\Models\Link;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RepublishUnpublishedLinksJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $type) {}

    public function handle(): void
    {
        $count = 0;

        Link::where('type', $this->type)
            ->where('status', '!=', 'published')
            ->select('id')
            ->lazy(100)
            ->each(function (Link $link) use (&$count) {
                $link->update(['status' => 'pending', 'failed_reason' => null]);
                dispatch(new PublishLinkJob($link));
                $count++;
            });

        Log::info("Republish unpublished {$this->type} links: {$count} queued");
    }
}