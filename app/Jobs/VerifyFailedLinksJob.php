<?php

namespace App\Jobs;

use App\Models\Link;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;

class VerifyFailedLinksJob implements ShouldQueue
{
    use Queueable;

    // A retry after a timeout would re-select links and dispatch a whole second batch of
    // VerifyFailedLinkJob for the same links — one attempt only.
    public int $tries = 1;

    // Same reasoning as AnalyzeLinksJob — building a batch of hundreds of VerifyFailedLinkJob
    // can outrun the default 60s job timeout on a large failed-link count.
    public int $timeout = 300;

    public function handle(): void
    {
        $ids = Link::query()->where('status', 'failed')->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        Bus::batch($ids->map(fn (int $id) => new VerifyFailedLinkJob($id))->all())
            ->name('verify-failed-links')
            ->allowFailures()
            ->dispatch();
    }
}
