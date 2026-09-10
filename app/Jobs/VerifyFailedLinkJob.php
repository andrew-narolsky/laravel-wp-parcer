<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\LinkAvailabilityChecker;
use App\Services\WordPressXmlRpcClient;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerifyFailedLinkJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public int $timeout = 150;

    public function __construct(public readonly int $linkId) {}

    public function handle(LinkAvailabilityChecker $checker): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $link = Link::find($this->linkId);

        // Only act on links still marked failed — one may have been retried/resolved
        // manually while this batch was queued.
        if (!$link || $link->status !== 'failed') {
            return;
        }

        $link->loadMissing('site');

        if (!$link->site) {
            Log::warning('VerifyFailedLinkJob: site not found, skipping', ['link_id' => $link->id]);
            return;
        }

        try {
            $wpUrl = $link->type === 'homepage'
                ? $this->findOnHomepage($checker, $link)
                : $this->findAsPost($checker, $link);
        } catch (Throwable $e) {
            Log::warning('VerifyFailedLinkJob: verification attempt failed', [
                'link_id' => $link->id, 'error' => $e->getMessage(),
            ]);
            return;
        }

        if ($wpUrl === null) {
            return;
        }

        $link->update([
            'status'        => 'published',
            'wp_url'        => $wpUrl,
            'failed_reason' => null,
            'check_status'  => 'alive',
            'check_error'   => null,
            'checked_at'    => now(),
        ]);

        Log::info('VerifyFailedLinkJob: found an existing publication for a failed link', [
            'link_id' => $link->id, 'wp_url' => $wpUrl,
        ]);
    }

    // WordPress XML-RPC has no "get post by title" call — 's' is passed through to the
    // underlying WP_Query as a best-effort narrowing, giving candidate permalinks. The actual
    // presence check then happens against each candidate's real, rendered page (not the raw
    // post_content XML-RPC returns) — a page builder like Elementor stores its layout
    // separately and leaves post_content empty/irrelevant, so raw content can never match.
    private function findAsPost(LinkAvailabilityChecker $checker, Link $link): ?string
    {
        $site = $link->site;

        $posts = WordPressXmlRpcClient::call($site, 'wp.getPosts', [
            0,
            $site->login,
            $site->password,
            ['post_type' => 'post', 's' => $link->title, 'number' => 20],
            ['post_title', 'post_type', 'link'],
        ]);

        foreach ($posts as $post) {
            if (($post['post_type'] ?? null) !== 'post' || ($post['post_title'] ?? null) !== $link->title) {
                continue;
            }

            $url = $post['link'] ?? null;

            if (!$url || !$this->pageHasLink($checker, $url, $link)) {
                continue;
            }

            return $url;
        }

        return null;
    }

    // The homepage's URL is always known (it's the site itself) — no XML-RPC lookup needed,
    // just check the live rendered front page the same way a normal availability check would.
    private function findOnHomepage(LinkAvailabilityChecker $checker, Link $link): ?string
    {
        $site = $link->site;

        return $this->pageHasLink($checker, $site->url, $link) ? $site->url : null;
    }

    private function pageHasLink(LinkAvailabilityChecker $checker, string $url, Link $link): bool
    {
        try {
            $body = $checker->fetchBody($url);
        } catch (Throwable $e) {
            Log::warning('VerifyFailedLinkJob: could not fetch candidate page', [
                'link_id' => $link->id, 'url' => $url, 'error' => $e->getMessage(),
            ]);
            return false;
        }

        return $checker->hasLink($body, $link);
    }
}
