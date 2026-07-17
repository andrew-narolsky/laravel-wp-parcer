@extends('layouts.admin')

@section('title', 'Links')

@section('content')
    <div class="page-header mb-3">
        <div class="title-wrapper mb-2">
            <div class="col-auto d-block">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-link menu-icon"></i>
                    </span> Links
                </h3>
            </div>
            <div class="col-auto ms-auto text-end mt-n1 d-flex gap-2">
                @php
                    $analyzeScope = collect([
                        $type ? ucfirst($type) : null,
                        $status ? ucfirst($status) : null,
                        $checkStatus ? ucfirst($checkStatus) : null,
                    ])->filter()->implode(', ');
                @endphp
                <form action="{{ route('admin.links.analyze', request()->query()) }}" method="POST"
                      class="ajax-confirm-form"
                      data-confirm="Run links analysis{{ $analyzeScope ? ' for: ' . $analyzeScope : '' }}? A report will be sent to email.">
                    @csrf
                    <button type="submit" class="btn btn-outline-info">
                        <i class="mdi mdi-magnify me-1"></i> Analyze
                    </button>
                </form>
                <a href="{{ route('admin.links.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="mdi mdi-download me-1"></i> Export CSV
                </a>
                <form action="{{ route('admin.links.republish_posts') }}" method="POST"
                      class="ajax-confirm-form" data-confirm="Retry publishing all unpublished post links?">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning">
                        <i class="mdi mdi-refresh me-1"></i> Republish Posts
                    </button>
                </form>
                <form action="{{ route('admin.links.republish_homepage') }}" method="POST"
                      class="ajax-confirm-form" data-confirm="Retry publishing all unpublished homepage links?">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning">
                        <i class="mdi mdi-refresh me-1"></i> Republish Homepage
                    </button>
                </form>
                @if(config('services.show_remove_buttons'))
                    <form action="{{ route('admin.links.remove_homepage_content') }}" method="POST"
                          class="ajax-confirm-form" data-confirm="Remove our content from all published homepage links? This edits the live pages and cannot be undone automatically.">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="mdi mdi-delete-sweep me-1"></i> Remove Homepage Content
                        </button>
                    </form>
                    <form action="{{ route('admin.links.remove_posts') }}" method="POST"
                          class="ajax-confirm-form" data-confirm="Delete all published post links from their sites? This deletes the live WordPress posts (including leftover duplicates from past publish bugs) and cannot be undone automatically.">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="mdi mdi-delete-sweep me-1"></i> Remove Posts
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.links.create') }}" class="btn btn-primary">Add Link</a>
            </div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Links</li>
            </ol>
        </nav>
    </div>

    @php
        $typeFilters = ['' => 'All', 'post' => 'Posts', 'homepage' => 'Homepage'];
        $statusFilters = ['' => 'All', 'published' => 'Published'];
        $checkFilters = ['' => 'All', 'alive' => 'Alive', 'not_found' => 'Not found', 'blocked' => 'Blocked', 'compromised' => 'Compromised'];
    @endphp
    <div class="btn-group mb-3 me-2" role="group">
        @foreach($typeFilters as $value => $label)
            @php
                $query = array_merge(request()->except(['type', 'page']), $value ? ['type' => $value] : []);
            @endphp
            <a href="{{ request()->url() . ($query ? '?' . http_build_query($query) : '') }}"
               class="btn {{ $type === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    <div class="btn-group mb-3 me-2" role="group">
        @foreach($statusFilters as $value => $label)
            @php
                $query = array_merge(request()->except(['status', 'page']), $value ? ['status' => $value] : []);
            @endphp
            <a href="{{ request()->url() . ($query ? '?' . http_build_query($query) : '') }}"
               class="btn {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    <div class="btn-group mb-3 me-2" role="group">
        @foreach($checkFilters as $value => $label)
            @php
                $query = array_merge(request()->except(['check_status', 'page']), $value ? ['check_status' => $value] : []);
            @endphp
            <a href="{{ request()->url() . ($query ? '?' . http_build_query($query) : '') }}"
               class="btn {{ $checkStatus === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    @php
        $projectFilters = ['' => 'All Projects'] + $projects->pluck('name', 'id')->all();
    @endphp
    <div class="w-100"></div>
    <div class="btn-group mb-3" role="group">
        @foreach($projectFilters as $value => $label)
            @php
                $query = array_merge(request()->except(['project_id', 'page']), $value !== '' ? ['project_id' => $value] : []);
            @endphp
            <a href="{{ request()->url() . ($query ? '?' . http_build_query($query) : '') }}"
               class="btn {{ $projectId == $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    @include('admin.partials.sortable-th', ['column' => 'site', 'label' => 'Site'])
                                    <th>Project</th>
                                    @include('admin.partials.sortable-th', ['column' => 'type', 'label' => 'Type'])
                                    @include('admin.partials.sortable-th', ['column' => 'wp_url', 'label' => 'Published URL'])
                                    @include('admin.partials.sortable-th', ['column' => 'status', 'label' => 'Status'])
                                    @include('admin.partials.sortable-th', ['column' => 'failed_reason', 'label' => 'Failed reason'])
                                    @include('admin.partials.sortable-th', ['column' => 'check_status', 'label' => 'Check'])
                                    @include('admin.partials.sortable-th', ['column' => 'created_at', 'label' => 'Added'])
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($links as $link)
                                    <tr>
                                        <td>{{ $links->firstItem() + $loop->index }}</td>
                                        <td><a href="{{ $link->site->url }}" target="_blank">{{ $link->site->name }}</a></td>
                                        <td>{{ $link->project?->name ?? '—' }}</td>
                                        <td>
                                            @if($link->type === 'post')
                                                <span class="badge badge-info">In post</span>
                                            @else
                                                <span class="badge badge-warning">Homepage</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($link->wp_url)
                                                <a href="{{ $link->wp_url }}" target="_blank" class="text-truncate d-inline-block" style="max-width:200px">
                                                    {{ $link->wp_url }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($link->status === 'published')
                                                <span class="badge badge-success">Published</span>
                                            @elseif($link->status === 'failed')
                                                <span class="badge badge-danger">Failed</span>
                                            @else
                                                <span class="badge badge-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($link->failed_reason)
                                                <span class="text-danger text-truncate d-inline-block" style="max-width:220px" title="{{ $link->failed_reason }}">
                                                    {{ $link->failed_reason }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($link->check_status === 'alive')
                                                <span class="badge badge-success" title="Checked {{ $link->checked_at?->format('d.m.Y H:i') }}">Alive</span>
                                            @elseif($link->check_status === 'not_found')
                                                <span class="badge badge-danger" title="{{ $link->check_error }} ({{ $link->checked_at?->format('d.m.Y H:i') }})">Not found</span>
                                            @elseif($link->check_status === 'blocked')
                                                <span class="badge badge-warning" title="{{ $link->check_error }} ({{ $link->checked_at?->format('d.m.Y H:i') }})">Blocked</span>
                                            @elseif($link->check_status === 'compromised')
                                                <span class="badge badge-dark" title="{{ $link->check_error }} ({{ $link->checked_at?->format('d.m.Y H:i') }})">Compromised</span>
                                            @else
                                                <span class="badge badge-secondary">Unknown</span>
                                            @endif
                                        </td>
                                        <td>{{ $link->created_at->format('d.m.Y') }}</td>
                                        <td class="d-flex flex-row justify-content-end">
                                            <form action="{{ route('admin.links.check', $link) }}" method="POST" class="d-inline me-2 ajax-quiet-form">
                                                @csrf
                                                <button type="submit" class="btn btn-inverse-secondary btn-icon" title="Check status">
                                                    <i class="mdi mdi-refresh"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.links.publish', $link) }}" method="POST" class="d-inline me-2 ajax-quiet-form">
                                                @csrf
                                                <button type="submit" class="btn btn-inverse-primary btn-icon"
                                                        @if($link->check_status === 'alive') disabled title="Already confirmed alive — republishing would create a duplicate post"
                                                        @else title="Publish to site" @endif>
                                                    <i class="mdi mdi-publish"></i>
                                                </button>
                                            </form>
                                            @if(config('services.show_remove_buttons'))
                                                @if($link->type === 'post')
                                                    <form action="{{ route('admin.links.remove_link_post', $link) }}" method="POST"
                                                          class="d-inline me-2 ajax-confirm-form"
                                                          data-confirm="Delete this published post from its site? This deletes the live WordPress post and cannot be undone automatically.">
                                                        @csrf
                                                        <button type="submit" class="btn btn-inverse-danger btn-icon" title="Remove published post">
                                                            <i class="mdi mdi-delete-sweep"></i>
                                                        </button>
                                                    </form>
                                                @elseif($link->type === 'homepage')
                                                    <form action="{{ route('admin.links.remove_link_homepage_content', $link) }}" method="POST"
                                                          class="d-inline me-2 ajax-confirm-form"
                                                          data-confirm="Remove our content from this published homepage link? This edits the live page and cannot be undone automatically.">
                                                        @csrf
                                                        <button type="submit" class="btn btn-inverse-danger btn-icon" title="Remove homepage content">
                                                            <i class="mdi mdi-delete-sweep"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                            <a href="{{ route('admin.links.edit', $link) }}" class="btn btn-inverse-info btn-icon me-2">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.links.destroy', $link) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete link?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-inverse-danger btn-icon">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">No links yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                {{ $links->links('admin.partials.pagination') }}
            </div>
        </div>
    </div>

@endsection
