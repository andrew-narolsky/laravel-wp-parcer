<?php

namespace App\Services;

use App\Models\Site;

class WordPressClient
{
    public function testConnection(Site $site): array
    {
        $blogs = WordPressXmlRpcClient::call($site, 'wp.getUsersBlogs', [$site->login, $site->password]);

        return ['success' => true, 'blogs' => $blogs];
    }
}
