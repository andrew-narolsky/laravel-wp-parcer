@extends('layouts.admin')

@section('title', 'Edit Site')

@section('content')
    <div class="page-header mb-3">
        <div class="title-wrapper mb-2">
            <div class="col-auto d-block">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-web menu-icon"></i>
                    </span> Edit site
                </h3>
            </div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.sites.index') }}">Sites</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">{{ $site->name }}</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.sites.update', $site) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $site->name) }}" placeholder="My site">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Site URL <span class="text-danger">*</span></label>
                            <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                                   value="{{ old('url', $site->url) }}" placeholder="https://example.com">
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">No trailing slash</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">WordPress Login <span class="text-danger">*</span></label>
                            <input type="text" name="login" class="form-control @error('login') is-invalid @enderror"
                                   value="{{ old('login', $site->login) }}" placeholder="admin">
                            @error('login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Leave blank to keep unchanged">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @php
                            $toRadioValue = fn ($value) => $value === null ? '' : ($value ? '1' : '0');
                            $isActiveValue = old('is_active', $toRadioValue($site->is_active));
                            $postsAvailableValue = old('posts_available', $toRadioValue($site->posts_available));
                            $homepageAvailableValue = old('homepage_available', $toRadioValue($site->homepage_available));
                        @endphp

                        <div class="mb-3">
                            <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                            <div class="radio-card-group">
                                <input type="radio" name="is_active" id="is_active_yes" value="1"
                                       {{ $isActiveValue === '1' ? 'checked' : '' }}>
                                <label for="is_active_yes">
                                    <i class="mdi mdi-check-circle-outline"></i> Active
                                </label>

                                <input type="radio" name="is_active" id="is_active_no" value="0"
                                       {{ $isActiveValue === '0' ? 'checked' : '' }}>
                                <label for="is_active_no">
                                    <i class="mdi mdi-close-circle-outline"></i> Inactive
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Posts Available</label>
                            <div class="radio-card-group">
                                <input type="radio" name="posts_available" id="posts_available_unknown" value=""
                                       {{ $postsAvailableValue === '' ? 'checked' : '' }}>
                                <label for="posts_available_unknown">
                                    <i class="mdi mdi-help-circle-outline"></i> Unknown
                                </label>

                                <input type="radio" name="posts_available" id="posts_available_yes" value="1"
                                       {{ $postsAvailableValue === '1' ? 'checked' : '' }}>
                                <label for="posts_available_yes">
                                    <i class="mdi mdi-check-circle-outline"></i> Yes
                                </label>

                                <input type="radio" name="posts_available" id="posts_available_no" value="0"
                                       {{ $postsAvailableValue === '0' ? 'checked' : '' }}>
                                <label for="posts_available_no">
                                    <i class="mdi mdi-close-circle-outline"></i> No
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-block">Homepage Available</label>
                            <div class="radio-card-group">
                                <input type="radio" name="homepage_available" id="homepage_available_unknown" value=""
                                       {{ $homepageAvailableValue === '' ? 'checked' : '' }}>
                                <label for="homepage_available_unknown">
                                    <i class="mdi mdi-help-circle-outline"></i> Unknown
                                </label>

                                <input type="radio" name="homepage_available" id="homepage_available_yes" value="1"
                                       {{ $homepageAvailableValue === '1' ? 'checked' : '' }}>
                                <label for="homepage_available_yes">
                                    <i class="mdi mdi-check-circle-outline"></i> Yes
                                </label>

                                <input type="radio" name="homepage_available" id="homepage_available_no" value="0"
                                       {{ $homepageAvailableValue === '0' ? 'checked' : '' }}>
                                <label for="homepage_available_no">
                                    <i class="mdi mdi-close-circle-outline"></i> No
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save"></i> Save
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
