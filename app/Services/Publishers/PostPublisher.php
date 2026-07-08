<?php

namespace App\Services\Publishers;

use App\Contracts\LinkPublisherContract;
use App\Models\Link;
use App\Models\Site;
use App\Services\WordPressXmlRpcClient;
use App\Services\XmlRpcBase64Value;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostPublisher implements LinkPublisherContract
{
    public function publish(Site $site, Link $link): array
    {
        $postFields = [
            'post_title'   => $link->title,
            'post_content' => $link->text,
            'post_status'  => 'publish',
        ];

        $thumbnailId = $this->uploadImage($site, $link);
        if ($thumbnailId !== null) {
            $postFields['post_thumbnail'] = $thumbnailId;
        }

        $postId = WordPressXmlRpcClient::call($site, 'wp.newPost', [
            0,
            $site->login,
            $site->password,
            $postFields,
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

    private function uploadImage(Site $site, Link $link): ?int
    {
        if (empty($link->image)) {
            return null;
        }

        $path = rtrim(config('services.link_images_path'), '/') . '/' . ltrim($link->image, '/');

        if (!is_file($path)) {
            Log::warning('PostPublisher: image not found, publishing without it', ['link_id' => $link->id, 'path' => $path]);
            return null;
        }

        try {
            $result = WordPressXmlRpcClient::call($site, 'wp.uploadFile', [
                0,
                $site->login,
                $site->password,
                [
                    'name' => basename($path),
                    'type' => mime_content_type($path) ?: 'application/octet-stream',
                    'bits' => new XmlRpcBase64Value(file_get_contents($path)),
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('PostPublisher: image upload failed, publishing without it', ['link_id' => $link->id, 'error' => $e->getMessage()]);
            return null;
        }

        return isset($result['id']) ? (int) $result['id'] : null;
    }
}
