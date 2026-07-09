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

    public function handle(): void
    {
        $jobs = Link::where('status', 'published')
            ->select('id')
            ->lazy(100)
            ->map(fn(Link $link) => new AnalyzeLinkJob($link->id));

        if ($jobs->isEmpty()) {
            dispatch(new SendLinksAnalysisReportJob());
            return;
        }

        Bus::batch($jobs->all())
            ->name('links-analysis')
            ->finally(fn(Batch $batch) => dispatch(new SendLinksAnalysisReportJob()))
            ->dispatch();
    }
}