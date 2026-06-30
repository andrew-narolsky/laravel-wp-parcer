<?php

namespace App\Services\Checkers;

use App\Contracts\LinkCheckerContract;
use App\DTO\LinkCheckResult;
use App\Models\Link;
use App\Models\Site;
use Illuminate\Support\Facades\Http;

class HomepageLinkChecker implements LinkCheckerContract
{
    public function check(Site $site, Link $link): LinkCheckResult
    {
        $http = Http::withBasicAuth($site->login, $site->password)->timeout(15);

        $settings = $http->get("{$site->url}/wp-json/wp/v2/settings");
        if (!$settings->successful()) {
            return new LinkCheckResult(
                $link,
                postExists: false,
                hasLink: false,
                error: "Cannot fetch settings: HTTP {$settings->status()}"
            );
        }

        $pageId = $settings->json('page_on_front');
        if (!$pageId) {
            return new LinkCheckResult(
                $link,
                postExists: false,
                hasLink: false,
                error: 'Front page is not set to a static page'
            );
        }

        $page = $http->get("{$site->url}/wp-json/wp/v2/pages/{$pageId}");
        if (!$page->successful()) {
            return new LinkCheckResult(
                $link,
                postExists: false,
                hasLink: false,
                error: "Cannot fetch front page: HTTP {$page->status()}"
            );
        }

        $content = $page->json('content.rendered') ?? '';

        return new LinkCheckResult(
            link: $link,
            postExists: true,
            hasLink: str_contains($content, $link->url),
        );
    }
}
