<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\User;
use App\Notifications\ReplaceHomepageContentFinished;
use App\Services\Publishers\HomepagePublisher;
use App\Services\WordPressXmlRpcClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ReplaceHomepageContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    // Same reasoning as PublishLinkJob — up to 3 sequential XML-RPC calls (findFrontPageId's
    // HTTP fetch plus wp.editPost) at up to 60s each.
    public int $timeout = 200;

    public function __construct(public readonly Site $site, public readonly string $text) {}

    public function handle(HomepagePublisher $homepagePublisher): void
    {
        $postId = $homepagePublisher->findFrontPageId($this->site);

        WordPressXmlRpcClient::call($this->site, 'wp.editPost', [
            0,
            $this->site->login,
            $this->site->password,
            $postId,
            ['post_content' => $this->text],
        ]);

        $this->site->update(['homepage_content' => $this->text]);

        Log::info('ReplaceHomepageContentJob done', ['site_id' => $this->site->id, 'post_id' => $postId]);

        Notification::send(User::all(), new ReplaceHomepageContentFinished($this->site, success: true));
    }

    public function failed(Throwable $exception): void
    {
        Notification::send(User::all(), new ReplaceHomepageContentFinished($this->site, success: false, reason: $exception->getMessage()));
    }
}
