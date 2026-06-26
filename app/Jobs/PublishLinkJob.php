<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\WordPressClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishLinkJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Link $link) {}

    public function handle(WordPressClient $client): void
    {
        match ($this->link->type) {
            'post'     => $client->publishPost($this->link->site, $this->link->title, $this->link->text),
            'homepage' => $client->updateFrontPage($this->link->site, $this->link->text),
        };

        $this->link->update(['is_active' => true]);
    }
}
