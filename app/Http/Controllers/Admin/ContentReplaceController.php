<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplaceContentRequest;
use App\Jobs\ReplaceHomepageContentJob;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContentReplaceController extends Controller
{
    public function create(): View
    {
        $sites = Site::where('is_active', true)->orderBy('name')->get();

        return view('admin.content_replace.create', compact('sites'));
    }

    public function store(ReplaceContentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $site = Site::findOrFail($data['site_id']);

        dispatch(new ReplaceHomepageContentJob($site, $data['text']));

        return redirect()->route('admin.content_replace.create')->with('success', 'Queued for content replacement.');
    }
}
