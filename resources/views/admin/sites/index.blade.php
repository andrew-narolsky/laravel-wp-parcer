@extends('layouts.admin')

@section('title', 'Sites')

@section('content')
    <div class="page-header mb-3">
        <div class="title-wrapper mb-2">
            <div class="col-auto d-block">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-web menu-icon"></i>
                    </span> Sites
                </h3>
            </div>

            <div class="col-auto ms-auto text-end mt-n1 d-flex gap-2">
                <form action="{{ route('admin.sites.import') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                    @csrf
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" class="d-none"
                           onchange="this.form.submit()">
                    <label for="csv_file" class="btn btn-outline-secondary mb-0" style="cursor:pointer">
                        <i class="mdi mdi-upload me-1"></i> Import Posts
                    </label>
                </form>
                <form action="{{ route('admin.sites.import_homepage') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                    @csrf
                    <input type="file" name="csv_file" id="csv_file_homepage" accept=".csv" class="d-none"
                           onchange="this.form.submit()">
                    <label for="csv_file_homepage" class="btn btn-outline-secondary mb-0" style="cursor:pointer">
                        <i class="mdi mdi-upload me-1"></i> Import Homepage
                    </label>
                </form>
                <a href="{{ route('admin.sites.create') }}" class="btn btn-primary">Add Site</a>
            </div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Sites</li>
            </ol>
        </nav>
    </div>

    @php
        $availabilityFilters = ['' => 'All', 'yes' => 'Yes', 'no' => 'No'];
    @endphp
    <div class="btn-group mb-3 me-2" role="group">
        @foreach($availabilityFilters as $value => $label)
            @php
                $query = array_merge(request()->except(['posts_available', 'page']), $value ? ['posts_available' => $value] : []);
            @endphp
            <a href="{{ request()->url() . ($query ? '?' . http_build_query($query) : '') }}"
               class="btn {{ $postsAvailable === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                Posts: {{ $label }}
            </a>
        @endforeach
    </div>
    <div class="btn-group mb-3" role="group">
        @foreach($availabilityFilters as $value => $label)
            @php
                $query = array_merge(request()->except(['homepage_available', 'page']), $value ? ['homepage_available' => $value] : []);
            @endphp
            <a href="{{ request()->url() . ($query ? '?' . http_build_query($query) : '') }}"
               class="btn {{ $homepageAvailable === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                Homepage: {{ $label }}
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
                                    @include('admin.partials.sortable-th', ['column' => 'name', 'label' => 'Name'])
                                    @include('admin.partials.sortable-th', ['column' => 'url', 'label' => 'URL'])
                                    <th>Login</th>
                                    @include('admin.partials.sortable-th', ['column' => 'is_active', 'label' => 'Status'])
                                    @include('admin.partials.sortable-th', ['column' => 'posts_available', 'label' => 'Posts'])
                                    @include('admin.partials.sortable-th', ['column' => 'homepage_available', 'label' => 'Homepage'])
                                    @include('admin.partials.sortable-th', ['column' => 'created_at', 'label' => 'Added'])
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sites as $site)
                                    <tr>
                                        <td>{{ $sites->firstItem() + $loop->index }}</td>
                                        <td>{{ $site->name }}</td>
                                        <td>
                                            <a href="{{ $site->url }}" target="_blank">
                                                {{ $site->url }}
                                            </a>
                                        </td>
                                        <td>{{ $site->login }}</td>
                                        <td>
                                            @if($site->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(is_null($site->posts_available))
                                                <span class="badge badge-secondary">Unknown</span>
                                            @elseif($site->posts_available)
                                                <span class="badge badge-success">Yes</span>
                                            @else
                                                <span class="badge badge-danger">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(is_null($site->homepage_available))
                                                <span class="badge badge-secondary">Unknown</span>
                                            @elseif($site->homepage_available)
                                                <span class="badge badge-success">Yes</span>
                                            @else
                                                <span class="badge badge-danger">No</span>
                                            @endif
                                        </td>
                                        <td>{{ $site->created_at->format('d.m.Y') }}</td>

                                        <td class="d-flex flex-row justify-content-end">
                                            <a href="{{ route('admin.sites.edit', $site) }}" type="button" class="btn btn-inverse-info btn-icon me-2">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>

                                            <form action="{{ route('admin.sites.destroy', $site) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete site?')">
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
                                        <td colspan="9" class="text-center text-muted py-4">No sites yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                {{ $sites->links('admin.partials.pagination') }}
            </div>
        </div>
    </div>

@endsection
