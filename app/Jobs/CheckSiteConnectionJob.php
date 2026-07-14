<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\WordPressClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckSiteConnectionJob implements ShouldQueue
{
    use Queueable;

    // A bit above the 60s ceiling of the single XML-RPC call this job makes, so a slow-but-alive
    // site gets caught by the try/catch below instead of the job being killed mid-request.
    public int $timeout = 75;

    public function __construct(public readonly Site $site) {}

    public function handle(WordPressClient $client): void
    {
        try {
            $client->testConnection($this->site);
            $this->site->update(['is_active' => true]);
        } catch (\Exception) {
            $this->site->update(['is_active' => false]);
        }
    }
}
