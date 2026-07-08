<?php

namespace App\Services\Publishers;

use App\Contracts\LinkPublisherContract;
use App\Models\Link;
use App\Models\Site;
use App\Services\WordPressXmlRpcClient;

class PostPublisher implements LinkPublisherContract
{
    public function publish(Site $site, Link $link): array
    {
        $postId = WordPressXmlRpcClient::call($site, 'wp.newPost', [
            0,
            $site->login,
            $site->password,
            [
                'post_title'   => $link->title,
                'post_content' => $link->text,
                'post_status'  => 'publish',
            ],
        ]);

        $post = WordPressXmlRpcClient::call($site, 'wp.getPost', [
            0,
            $site->login,
            $site->password,
            $postId,
            ['link', 'post_status'],
        ]);

        return [
            'id'     => $postId,
            'link'   => $post['link'] ?? null,
            'status' => $post['post_status'] ?? null,
        ];
    }
}
