<?php

namespace App\Jobs;

use App\DTO\LinkCheckResult;
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
            $outcome = $link->type === 'homepage'
                ? $this->checkHomepage($checker, $link)
                : $this->checkAsPost($checker, $link);
        } catch (Throwable $e) {
            Log::warning('VerifyFailedLinkJob: verification attempt failed', [
                'link_id' => $link->id, 'error' => $e->getMessage(),
            ]);
            return;
        }

        if ($outcome === null) {
            return;
        }

        [$url, $result] = $outcome;

        if ($result->hasLink) {
            $link->update([
                'status'        => 'published',
                'wp_url'        => $url,
                'failed_reason' => null,
                'check_status'  => 'alive',
                'check_error'   => null,
                'checked_at'    => now(),
            ]);

            Log::info('VerifyFailedLinkJob: found an existing publication for a failed link', [
                'link_id' => $link->id, 'wp_url' => $url,
            ]);

            return;
        }

        // Blocked or compromised (or, for a homepage, a definitive "not there") — we haven't
        // confirmed our link is live, so status stays failed, but the check_status/check_error
        // signal is real and worth keeping instead of leaving the row silently untouched.
        $link->update([
            'check_status' => $result->status(),
            'check_error'  => $result->failReason(),
            'checked_at'   => now(),
        ]);

        Log::info('VerifyFailedLinkJob: candidate page could not confirm the link', [
            'link_id' => $link->id, 'url' => $url, 'check_status' => $result->status(),
        ]);
    }

    // WordPress XML-RPC has no "get post by title" call — 's' is passed through to the
    // underlying WP_Query as a best-effort narrowing, giving candidate permalinks. The actual
    // presence check then happens against each candidate's real, rendered page (not the raw
    // post_content XML-RPC returns) — a page builder like Elementor stores its layout
    // separately and leaves post_content empty/irrelevant, so raw content can never match.
    // A blocked/compromised candidate doesn't rule out other candidates sharing the same
    // title, so keep looking, but remember the first such result in case nothing better turns up.
    private function checkAsPost(LinkAvailabilityChecker $checker, Link $link): ?array
    {
        $site = $link->site;

        $posts = WordPressXmlRpcClient::call($site, 'wp.getPosts', [
            0,
            $site->login,
            $site->password,
            ['post_type' => 'post', 's' => $link->title, 'number' => 20],
            ['post_title', 'post_type', 'link'],
        ]);

        $inconclusive = null;

        foreach ($posts as $post) {
            if (($post['post_type'] ?? null) !== 'post' || ($post['post_title'] ?? null) !== $link->title) {
                continue;
            }

            $url = $post['link'] ?? null;

            if (!$url) {
                continue;
            }

            $result = $this->evaluateUrl($checker, $url, $link);

            if ($result === null) {
                continue;
            }

            if ($result->hasLink) {
                return [$url, $result];
            }

            if (($result->blocked || $result->compromised) && $inconclusive === null) {
                $inconclusive = [$url, $result];
            }
        }

        return $inconclusive;
    }

    // The homepage's URL is always known (it's the site itself) — no XML-RPC lookup needed,
    // just check the live rendered front page the same way a normal availability check would.
    private function checkHomepage(LinkAvailabilityChecker $checker, Link $link): ?array
    {
        $site = $link->site;
        $result = $this->evaluateUrl($checker, $site->url, $link);

        return $result ? [$site->url, $result] : null;
    }

    private function evaluateUrl(LinkAvailabilityChecker $checker, string $url, Link $link): ?LinkCheckResult
    {
        try {
            $body = $checker->fetchBody($url);
        } catch (Throwable $e) {
            Log::warning('VerifyFailedLinkJob: could not fetch candidate page', [
                'link_id' => $link->id, 'url' => $url, 'error' => $e->getMessage(),
            ]);
            return null;
        }

        return $checker->evaluate($body, $link);
    }
}
