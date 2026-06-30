<?php

namespace App\Services\Checkers;

use App\Contracts\LinkCheckerContract;
use App\DTO\LinkCheckResult;
use App\Models\Link;
use App\Models\Site;
use Illuminate\Support\Facades\Http;

class PostLinkChecker implements LinkCheckerContract
{
    public function check(Site $site, Link $link): LinkCheckResult
    {
        if (!$link->wp_url) {
            return new LinkCheckResult($link, postExists: false, hasLink: false, error: 'No published URL');
        }

        $slug = basename(parse_url($link->wp_url, PHP_URL_PATH));
        $http = Http::withBasicAuth($site->login, $site->password)->timeout(15);

        $post = null;
        foreach (['posts', 'pages'] as $type) {
            $response = $http->get("{$site->url}/wp-json/wp/v2/{$type}", ['slug' => $slug]);
            if ($response->successful() && count($response->json()) > 0) {
                $post = $response->json()[0];
                break;
            }
        }

        if (!$post) {
            return new LinkCheckResult($link, postExists: false, hasLink: false);
        }

        $content = $post['content']['rendered'] ?? '';

        return new LinkCheckResult(
            link: $link,
            postExists: true,
            hasLink: str_contains($content, $link->url),
        );
    }
}
