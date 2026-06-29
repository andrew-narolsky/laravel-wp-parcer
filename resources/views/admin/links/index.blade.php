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
                <form action="{{ route('admin.links.import') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                    @csrf
                    <input type="file" name="csv_file" id="links_csv_file" accept=".csv" class="d-none"
                           onchange="this.form.submit()">
                    <label for="links_csv_file" class="btn btn-outline-secondary mb-0" style="cursor:pointer">
                        <i class="mdi mdi-upload me-1"></i> Import CSV
                    </label>
                </form>
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
                                    <th>Site</th>
                                    <th>URL</th>
                                    <th>Anchor</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($links as $link)
                                    <tr>
                                        <td>{{ $link->id }}</td>
                                        <td>{{ $link->site->name }}</td>
                                        <td>
                                            <a href="{{ $link->url }}" target="_blank">
                                                {{ Str::limit($link->url, 40) }}
                                            </a>
                                        </td>
                                        <td>{{ $link->anchor }}</td>
                                        <td>
                                            @if($link->type === 'post')
                                                <span class="badge badge-info">In post</span>
                                            @else
                                                <span class="badge badge-warning">Homepage</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($link->is_active)
                                                <span class="badge badge-success">Published</span>
                                            @else
                                                <span class="badge badge-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ $link->created_at->format('d.m.Y') }}</td>
                                        <td class="d-flex flex-row justify-content-end">
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
                                        <td colspan="8" class="text-center text-muted py-4">No links yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
