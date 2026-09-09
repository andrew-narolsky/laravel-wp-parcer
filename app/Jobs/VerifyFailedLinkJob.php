<?php

namespace App\Jobs;

use App\Models\Link;
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

    public function handle(HomepagePublisher $homepagePublisher): void
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
                ? $this->findOnHomepage($homepagePublisher, $link)
                : $this->findAsPost($link);
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
    private function findAsPost(Link $link): ?string
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
            if ($this->matchesOurLink($post, $link)) {
                return $post['link'] ?? null;
            }
        }

        return null;
    }

    private function findOnHomepage(HomepagePublisher $homepagePublisher, Link $link): ?string
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

        if (!str_contains($post['post_content'] ?? '', $link->text)) {
            return null;
        }

        return $post['link'] ?? $site->url;
    }

    private function matchesOurLink(array $post, Link $link): bool
    {
        return ($post['post_type'] ?? null) === 'post'
            && ($post['post_title'] ?? null) === $link->title
            && str_contains($post['post_content'] ?? '', $link->text);
    }
}
