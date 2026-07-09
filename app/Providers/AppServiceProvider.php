<?php

namespace App\Providers;

use App\Services\LinkPublisher;
use App\Services\Publishers\HomepagePublisher;
use App\Services\Publishers\PostPublisher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LinkPublisher::class, fn($app) => new LinkPublisher([
            'post'     => $app->make(PostPublisher::class),
            'homepage' => $app->make(HomepagePublisher::class),
        ]));
    }

    public function boot(): void {}
}
