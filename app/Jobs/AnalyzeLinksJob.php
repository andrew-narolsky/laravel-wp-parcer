<?php

namespace App\Jobs;

use App\Models\Link;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;

class AnalyzeLinksJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $type = '',
        public readonly string $status = '',
        public readonly string $checkStatus = '',
    ) {}

    public function handle(): void
    {
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