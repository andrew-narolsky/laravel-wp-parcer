<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\Publishers\HomepagePublisher;
use App\Services\WordPressXmlRpcClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RemoveHomepageLinkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    // Same reasoning as PublishLinkJob — up to 3 sequential XML-RPC calls at up to 60s each.
    public int $timeout = 200;

    public function __construct(public readonly Link $link) {}

    public function handle(HomepagePublisher $homepagePublisher): void
    {
        $link = $this->link->load('site');

        if (!$link->site) {
            Log::warning('RemoveHomepageLinkJob: site not found, skipping', ['link_id' => $link->id]);
            return;
        }

        $site = $link->site;

        $postId = $homepagePublisher->findFrontPageId($site);

        $post = WordPressXmlRpcClient::call($site, 'wp.getPost', [
            0,
            $site->login,
            $site->password,
            $postId,
            ['post_content'],
        ]);

        $content = $post['post_content'] ?? '';

        // Exact-match removal on purpose — removes every occurrence of the fragment we
        // actually inserted (there can be more than one from an earlier duplicate-publish
        // bug) without touching content we didn't add ourselves.
        $cleaned = str_replace($link->text, '', $content);

        if ($cleaned !== $content) {
            WordPressXmlRpcClient::call($site, 'wp.editPost', [
                0,
                $site->login,
                $site->password,
                $postId,
                ['post_content' => $cleaned],
            ]);
        }

        Log::info('RemoveHomepageLinkJob done', [
            'link_id'    => $link->id,
            'post_id'    => $postId,
            'was_found'  => $cleaned !== $content,
        ]);

        $link->update(['status' => 'pending', 'wp_url' => null, 'failed_reason' => null]);
    }

    public function failed(Throwable $exception): void
    {
        // The content is presumably still live on the site — leave status/wp_url alone,
        // just record why the cleanup attempt failed.
        $this->link->update(['failed_reason' => $exception->getMessage()]);
    }
}