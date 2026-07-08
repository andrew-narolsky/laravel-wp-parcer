<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLinkRequest;
use App\Http\Requests\Admin\UpdateLinkRequest;
use App\Jobs\AnalyzeLinksJob;
use App\Jobs\PublishLinkJob;
use App\Models\Link;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LinkController extends Controller
{
    public function index(): View
    {
        $links = Link::with('site')->latest()->paginate(50);

        return view('admin.links.index', compact('links'));
    }

    public function create(): View
    {
        $sites = Site::where('is_active', true)->orderBy('name')->get();

        return view('admin.links.create', compact('sites'));
    }

    public function store(StoreLinkRequest $request): RedirectResponse
    {
        $link = Link::create($request->validated());

        return redirect()->route('admin.links.edit', $link)->with('success', 'Link added');
    }

    public function edit(Link $link): View
    {
        $sites = Site::where('is_active', true)->orderBy('name')->get();

        return view('admin.links.edit', compact('link', 'sites'));
    }

    public function update(UpdateLinkRequest $request, Link $link): RedirectResponse
    {
        $link->update($request->validated());

        return redirect()->route('admin.links.edit', $link)->with('success', 'Link updated');
    }

    public function destroy(Link $link): RedirectResponse
    {
        $link->delete();

        return redirect()->route('admin.links.index')->with('success', 'Link deleted');
    }

    public function publish(Link $link): RedirectResponse
    {
        dispatch(new PublishLinkJob($link));

        return redirect()->back()->with('success', 'Queued for publishing.');
    }

    public function analyze(): RedirectResponse
    {
        dispatch(new AnalyzeLinksJob());

        return redirect()->back()->with('success', 'Analysis started. Report will be sent to ' . config('services.report_email') . '.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'links-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['id', 'site', 'title', 'url', 'wp_url', 'anchor', 'text', 'image', 'type', 'status', 'failed_reason']);

            Link::with('site')->orderBy('id')->lazy(500)->each(function (Link $link) use ($handle) {
                fputcsv($handle, [
                    $link->id,
                    $link->site->name ?? '',
                    $link->title,
                    $link->url,
                    $link->wp_url,
                    $link->anchor,
                    $link->text,
                    $link->image,
                    $link->type,
                    $link->status,
                    $link->failed_reason,
                ]);
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
