<?php

namespace App\Services\Publishers;

use App\Contracts\LinkPublisherContract;
use App\Models\Link;
use App\Models\Site;
use App\Services\WordPressHttpClient;
use RuntimeException;

class PostPublisher implements LinkPublisherContract
{
    public function publish(Site $site, Link $link): array
    {
        $response = WordPressHttpClient::for($site, 30)
            ->post("{$site->url}/wp-json/wp/v2/posts", [
                'title'   => $link->title,
                'content' => $link->text,
                'status'  => 'publish',
            ]);

        if (!$response->successful()) {
            throw new RuntimeException("HTTP {$response->status()}: " . $response->json('message', ''));
        }

        return $response->json();
    }
}
