<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLinkRequest;
use App\Http\Requests\Admin\UpdateLinkRequest;
use App\Jobs\AnalyzeLinksJob;
use App\Jobs\ImportLinksFromCsvJob;
use App\Jobs\PublishLinkJob;
use App\Models\Link;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function index(): View
    {
        $links = Link::with('site')->latest()->get();

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

        return redirect()->back()->with('success', 'Analysis started. Report will be sent to ' . env('REPORT_EMAIL') . '.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:102400'],
        ]);

        $path = $request->file('csv_file')->store('imports');

        dispatch(new ImportLinksFromCsvJob($path));

        return redirect()->route('admin.links.index')
            ->with('success', 'CSV import started. Links will appear shortly.');
    }
}
