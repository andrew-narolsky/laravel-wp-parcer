<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSiteRequest;
use App\Http\Requests\Admin\UpdateSiteRequest;
use App\Jobs\CheckSiteConnectionJob;
use App\Jobs\ImportSitesFromCsvJob;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(): View
    {
        $sites = Site::latest()->get();

        return view('admin.sites.index', compact('sites'));
    }

    public function create(): View
    {
        return view('admin.sites.create');
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $site = Site::create($request->validated());

        dispatch(new CheckSiteConnectionJob($site));

        return redirect()->route('admin.sites.index')->with('success', 'Site added');
    }

    public function edit(Site $site): View
    {
        return view('admin.sites.edit', compact('site'));
    }

    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());

        dispatch(new CheckSiteConnectionJob($site));

        return redirect()->route('admin.sites.index')->with('success', 'Site updated');
    }

    public function destroy(Site $site): RedirectResponse
    {
        $site->delete();

        return redirect()->route('admin.sites.index')->with('success', 'Site deleted');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:102400'],
        ]);

        $path = $request->file('csv_file')->store('imports');

        dispatch(new ImportSitesFromCsvJob($path));

        return redirect()->route('admin.sites.index')
            ->with('success', 'CSV import started. Sites will appear shortly.');
    }
}
