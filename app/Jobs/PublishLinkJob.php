<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\WordPressClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PublishLinkJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Link $link) {}

    public function handle(WordPressClient $client): void
    {
        $result = match ($this->link->type) {
            'post'     => $client->publishPost($this->link->site, $this->link->title, $this->link->text),
            'homepage' => $client->updateFrontPage($this->link->site, $this->link->text),
        };

        $wpUrl = $result['link'] ?? $result['guid']['rendered'] ?? null;

        Log::info('PublishLinkJob done', [
            'link_id' => $this->link->id,
            'type'    => $this->link->type,
            'wp_id'   => $result['id'] ?? null,
            'wp_link' => $wpUrl,
            'status'  => $result['status'] ?? null,
        ]);

        $this->link->update(['is_active' => true, 'wp_url' => $wpUrl]);
    }
}
