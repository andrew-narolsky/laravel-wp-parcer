<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\User;
use App\Notifications\RemovePostsFinished;
use App\Notifications\RemovePostsStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RemovePublishedPostsJob implements ShouldQueue
{
    use Queueable;

    // A retry would re-select still-published links and dispatch a second removal job for
    // each one already queued by the first attempt.
    public int $tries = 1;

    public int $timeout = 3600;

    public function handle(): void
    {
        $links = Link::where('type', 'post')
            ->where('status', 'published')
            ->get();

        Notification::send(User::all(), new RemovePostsStarted($links->count()));

        foreach ($links as $link) {
            dispatch(new RemovePublishedPostJob($link));
        }

        Log::info("Remove published posts: {$links->count()} queued");

        Notification::send(User::all(), new RemovePostsFinished($links->count()));
    }
}