<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\LinkAvailabilityChecker;
use App\Services\Publishers\HomepagePublisher;
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

    public function handle(HomepagePublisher $homepagePublisher, LinkAvailabilityChecker $checker): void
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
                ? $this->findOnHomepage($homepagePublisher, $checker, $link)
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
    // underlying WP_Query as a best-effort narrowing, then each candidate is verified locally
    // the same way RemovePublishedPostJob::matchesOurLink() does.
    private function findAsPost(LinkAvailabilityChecker $checker, Link $link): ?string
    {
        $site = $link->site;

        $posts = WordPressXmlRpcClient::call($site, 'wp.getPosts', [
            0,
            $site->login,
            $site->password,
            ['post_type' => 'post', 's' => $link->title, 'number' => 20],
            ['post_title', 'post_content', 'post_type', 'link'],
        ]);

        foreach ($posts as $post) {
            if ($this->matchesOurLink($checker, $post, $link)) {
                return $post['link'] ?? null;
            }
        }

        return null;
    }

    private function findOnHomepage(HomepagePublisher $homepagePublisher, LinkAvailabilityChecker $checker, Link $link): ?string
    {
        $site = $link->site;
        $postId = $homepagePublisher->findFrontPageId($site);

        $post = WordPressXmlRpcClient::call($site, 'wp.getPost', [
            0,
            $site->login,
            $site->password,
            $postId,
            ['post_content', 'link'],
        ]);

        if (!$checker->hasLink($post['post_content'] ?? '', $link)) {
            return null;
        }

        return $post['link'] ?? $site->url;
    }

    // A title match alone is too weak (titles can repeat across posts) and a raw text
    // substring match is both too weak (matches shared boilerplate around a different link)
    // and too strict (WordPress reformats content on save, e.g. wpautop). The real signal is
    // the same one LinkAvailabilityChecker uses for a normal check: is our specific
    // <a href="$link->url">$link->anchor</a> actually present.
    private function matchesOurLink(LinkAvailabilityChecker $checker, array $post, Link $link): bool
    {
        return ($post['post_type'] ?? null) === 'post'
            && ($post['post_title'] ?? null) === $link->title
            && $checker->hasLink($post['post_content'] ?? '', $link);
    }
}
