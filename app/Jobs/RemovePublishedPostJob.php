<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\Site;
use App\Services\Publishers\PostPublisher;
use App\Services\WordPressXmlRpcClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class RemovePublishedPostJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    // Main post + a handful of duplicate candidates, each up to an HTTP probe plus two
    // XML-RPC calls (getPost + deletePost).
    public int $timeout = 200;

    public function __construct(public readonly Link $link) {}

    public function handle(PostPublisher $postPublisher): void
    {
        $link = $this->link->load('site');

        if (!$link->site) {
            Log::warning('RemovePublishedPostJob: site not found, skipping', ['link_id' => $link->id]);
            return;
        }

        $site = $link->site;

        $postId = $postPublisher->resolvePostId($link->wp_url);

        if ($postId === null) {
            throw new RuntimeException("Could not resolve post ID from {$link->wp_url}");
        }

        $post = WordPressXmlRpcClient::call($site, 'wp.getPost', [
            0,
            $site->login,
            $site->password,
            $postId,
            ['post_title', 'post_content', 'post_type'],
        ]);

        if (!$this->matchesOurLink($post, $link)) {
            throw new RuntimeException("Post {$postId} title/content no longer matches this link — refusing to delete.");
        }

        $this->deleteDuplicates($postPublisher, $site, $link, $postId);

        WordPressXmlRpcClient::call($site, 'wp.deletePost', [0, $site->login, $site->password, $postId]);

        Log::info('RemovePublishedPostJob done', ['link_id' => $link->id, 'post_id' => $postId]);

        $link->update([
            'status'        => 'pending',
            'wp_url'        => null,
            'failed_reason' => null,
            'check_status'  => 'unknown',
            'check_error'   => null,
            'checked_at'    => null,
        ]);
    }

    // Past retry bugs sometimes published the same link more than once, and WordPress dedupes
    // the slug by appending -2, -3, etc. Given the current post is slug-N, only slug-(N-1)
    // down to the bare slug can be leftover duplicates — anything above N would have to
    // postdate the post we already know about, which can't happen. Each candidate is only
    // deleted if its title and content still match this link, so an unrelated post that
    // happens to share the slug pattern is left alone.
    private function deleteDuplicates(PostPublisher $postPublisher, Site $site, Link $link, int $currentPostId): void
    {
        $slug = $this->slugFromUrl($link->wp_url);
        [$baseSlug, $n] = $this->parseSlugSuffix($slug);

        for ($k = $n - 1; $k >= 1; $k--) {
            $candidateSlug = $k === 1 ? $baseSlug : "{$baseSlug}-{$k}";
            $candidateUrl = $this->urlWithSlug($link->wp_url, $candidateSlug);

            $candidateId = $postPublisher->resolvePostId($candidateUrl);

            if ($candidateId === null || $candidateId === $currentPostId) {
                continue;
            }

            try {
                $candidatePost = WordPressXmlRpcClient::call($site, 'wp.getPost', [
                    0,
                    $site->login,
                    $site->password,
                    $candidateId,
                    ['post_title', 'post_content', 'post_type'],
                ]);

                if (!$this->matchesOurLink($candidatePost, $link)) {
                    Log::info('RemovePublishedPostJob: duplicate candidate did not match, skipping', [
                        'link_id' => $link->id, 'candidate_url' => $candidateUrl, 'candidate_id' => $candidateId,
                    ]);
                    continue;
                }

                WordPressXmlRpcClient::call($site, 'wp.deletePost', [0, $site->login, $site->password, $candidateId]);

                Log::info('RemovePublishedPostJob: deleted duplicate', [
                    'link_id' => $link->id, 'candidate_url' => $candidateUrl, 'candidate_id' => $candidateId,
                ]);
            } catch (Throwable $e) {
                Log::warning('RemovePublishedPostJob: failed to delete duplicate candidate', [
                    'link_id' => $link->id, 'candidate_url' => $candidateUrl, 'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function matchesOurLink(array $post, Link $link): bool
    {
        return ($post['post_type'] ?? null) === 'post'
            && ($post['post_title'] ?? null) === $link->title
            && str_contains($post['post_content'] ?? '', $link->text);
    }

    private function slugFromUrl(string $url): string
    {
        $path = rtrim(parse_url($url, PHP_URL_PATH) ?? '', '/');
        $segments = explode('/', $path);

        return end($segments) ?: '';
    }

    private function parseSlugSuffix(string $slug): array
    {
        if (preg_match('/^(.+)-(\d+)$/', $slug, $matches)) {
            return [$matches[1], (int) $matches[2]];
        }

        return [$slug, 1];
    }

    private function urlWithSlug(string $url, string $slug): string
    {
        $parts = parse_url($url);
        $segments = explode('/', rtrim($parts['path'] ?? '/', '/'));
        array_pop($segments);
        $segments[] = $slug;

        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . implode('/', $segments) . '/';
    }

    public function failed(Throwable $exception): void
    {
        // The post is presumably still live — leave status/wp_url alone, just record why.
        $this->link->update(['failed_reason' => $exception->getMessage()]);
    }
}