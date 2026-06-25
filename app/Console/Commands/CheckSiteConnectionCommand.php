<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\WordPressClient;
use Illuminate\Console\Command;

class CheckSiteConnectionCommand extends Command
{
    protected $signature = 'sites:check {site? : Site ID (checks all sites if omitted)}';

    protected $description = 'Check WordPress connection for one or all sites';

    public function handle(WordPressClient $client): int
    {
        $sites = $this->argument('site')
            ? Site::where('id', $this->argument('site'))->get()
            : Site::all();

        if ($sites->isEmpty()) {
            $this->warn('No sites found.');
            return self::SUCCESS;
        }

        foreach ($sites as $site) {
            try {
                $client->testConnection($site);
                $site->update(['is_active' => true]);
                $this->info("[OK] {$site->name} ({$site->url})");
            } catch (\Exception $e) {
                $site->update(['is_active' => false]);
                $this->error("[FAIL] {$site->name} ({$site->url}): {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
