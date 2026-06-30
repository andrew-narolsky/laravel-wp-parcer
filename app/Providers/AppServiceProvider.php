<?php

namespace App\Providers;

use App\Services\Checkers\HomepageLinkChecker;
use App\Services\Checkers\PostLinkChecker;
use App\Services\LinkAnalyzer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LinkAnalyzer::class, fn($app) => new LinkAnalyzer([
            'post'     => $app->make(PostLinkChecker::class),
            'homepage' => $app->make(HomepageLinkChecker::class),
        ]));
    }

    public function boot(): void {}
}
