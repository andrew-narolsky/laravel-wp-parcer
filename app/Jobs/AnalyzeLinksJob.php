<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\User;
use App\Notifications\AnalyzeStarted;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;

class AnalyzeLinksJob implements ShouldQueue
{
    use Queueable;

    // A retry after a timeout would re-select links and dispatch a whole second batch of
    // AnalyzeLinkJob for the same links — one attempt only.
    public int $tries = 1;

    // Same reasoning as RepublishUnpublishedLinksJob — building a batch of hundreds of
    // AnalyzeLinkJob can outrun the default 60s job timeout on a large link count.
    public int $timeout = 300;

    public function __construct(
        public readonly string $type = '',
        public readonly string $status = '',
        public readonly string $checkStatus = '',
    ) {}

    public function handle(): void
    {
        Notification::send(User::all(), new AnalyzeStarted());

        $ids = Link::query()
            ->when($this->type, fn ($query) => $query->where('type', $this->type))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->checkStatus, fn ($query) => $query->where('check_status', $this->checkStatus))
            ->pluck('id');

        if ($ids->isEmpty()) {
            dispatch(new SendLinksAnalysisReportJob($ids->all()));
            return;
        }

        Bus::batch($ids->map(fn (int $id) => new AnalyzeLinkJob($id))->all())
            ->name('links-analysis')
            ->allowFailures()
            ->finally(fn(Batch $batch) => dispatch(new SendLinksAnalysisReportJob($ids->all())))
            ->dispatch();
    }
}