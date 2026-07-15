<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\User;
use App\Notifications\LinkPublishFinished;
use App\Services\LinkPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class PublishLinkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    // Worst case is 3 sequential XML-RPC calls (HomepagePublisher: getPost, editPost, getPost)
    // at up to 60s each — must exceed that or the job gets killed mid-publish on a slow site.
    public int $timeout = 200;

    /** @param bool $notify Only true for a manual single-link "Publish" click — bulk imports/republishes stay quiet to avoid spamming a toast per link. */
    public function __construct(public readonly Link $link, public readonly bool $notify = false) {}

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

        if ($this->notify) {
            Notification::send(User::all(), new LinkPublishFinished($link, success: true));
        }

        dispatch(new AnalyzeLinkJob($link->id));
    }

    public function failed(Throwable $exception): void
    {
        $this->link->update(['status' => 'failed', 'failed_reason' => $exception->getMessage()]);

        if ($this->notify) {
            Notification::send(User::all(), new LinkPublishFinished($this->link, success: false, reason: $exception->getMessage()));
        }
    }
}
