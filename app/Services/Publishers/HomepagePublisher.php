<?php

namespace App\Services\Publishers;

use App\Contracts\LinkPublisherContract;
use App\Models\Link;
use App\Models\Site;
use App\Services\WordPressXmlRpcClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HomepagePublisher implements LinkPublisherContract
{
    public function publish(Site $site, Link $link): array
    {
        $postId = $this->findFrontPageId($site);

        $post = WordPressXmlRpcClient::call($site, 'wp.getPost', [
            0,
            $site->login,
            $site->password,
            $postId,
            ['post_content'],
        ]);

        $newContent = $this->insertBeforeFirstLinkParagraph($post['post_content'] ?? '', $link->text);

        WordPressXmlRpcClient::call($site, 'wp.editPost', [
            0,
            $site->login,
            $site->password,
            $postId,
            ['post_content' => $newContent],
        ]);

        $updated = WordPressXmlRpcClient::call($site, 'wp.getPost', [
            0,
            $site->login,
            $site->password,
            $postId,
            ['link', 'post_status'],
        ]);

        return [
            'id'     => $postId,
            'link'   => $updated['link'] ?? null,
            'status' => $updated['post_status'] ?? null,
        ];
    }

    private function insertBeforeFirstLinkParagraph(string $content, string $fragment): string
    {
        preg_match_all('/<p\b[^>]*>.*?<\/p>/is', $content, $paragraphs, PREG_OFFSET_CAPTURE);

        foreach ($paragraphs[0] as [$paragraph, $offset]) {
            if (preg_match('/<a\s[^>]*>/i', $paragraph)) {
                return substr($content, 0, $offset) . $fragment . "\n" . substr($content, $offset);
            }
        }

        return $content . "\n" . $fragment;
    }

    private function findFrontPageId(Site $site): int
    {
        $response = Http::timeout(30)->get($site->url);

        if (!$response->successful()) {
            throw new RuntimeException("Cannot fetch homepage: HTTP {$response->status()}");
        }

        $html = $response->body();

        if (preg_match('/<link[^>]*rel=["\']shortlink["\'][^>]*>/i', $html, $tag)
            && preg_match('/href=["\'][^"\']*[?&]p=(\d+)["\']/i', $tag[0], $matches)
        ) {
            return (int) $matches[1];
        }

        if (preg_match('/<body[^>]*class=["\'][^"\']*\b(?:page-id|postid)-(\d+)\b/i', $html, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/<body[^>]*class=["\'][^"\']*\bhome\b/i', $html)) {
            throw new RuntimeException('Front page shows a post listing, not a static page — nothing to append content to.');
        }

        throw new RuntimeException('Could not determine front page ID from homepage markup.');
    }
}
