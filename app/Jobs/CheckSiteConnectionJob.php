<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\WordPressClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckSiteConnectionJob implements ShouldQueue
{
    use Queueable;

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
