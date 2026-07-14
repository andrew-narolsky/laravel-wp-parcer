<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\LinkPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishLinkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly Link $link) {}

    public function handle(LinkPublisher $publisher): void
    {
        $link = $this->link->load('site');

        if (!$link->site) {
            Log::warning('PublishLinkJob: site not found, skipping', ['link_id' => $link->id]);
            return;
        }

        $result = $publisher->publish($link);

        $wpUrl = $result['link'] ?? $result['guid']['rendered'] ?? null;

        Log::info('PublishLinkJob done', [
            'link_id' => $link->id,
            'type'    => $link->type,
            'wp_id'   => $result['id'] ?? null,
            'wp_link' => $wpUrl,
            'status'  => $result['status'] ?? null,
        ]);

        $link->update(['status' => 'published', 'wp_url' => $wpUrl, 'failed_reason' => null]);

        dispatch(new AnalyzeLinkJob($link->id));
    }

    public function failed(Throwable $exception): void
    {
        $this->link->update(['status' => 'failed', 'failed_reason' => $exception->getMessage()]);
    }
}
