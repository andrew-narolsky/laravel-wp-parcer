<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WordPressHttpClient
{
    public static function for(Site $site, int $timeout = 15): PendingRequest
    {
        return Http::withBasicAuth($site->login, $site->password)->timeout($timeout);
    }
}
