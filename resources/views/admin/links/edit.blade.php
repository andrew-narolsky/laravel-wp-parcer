@extends('layouts.admin')

@section('title', 'Edit Link')

@section('styles')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
@endsection

@section('content')
    <div class="page-header mb-3">
        <div class="title-wrapper mb-2">
            <div class="col-auto d-block">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-link menu-icon"></i>
                    </span> Edit link
                </h3>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <form action="{{ route('admin.links.publish', $link) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-publish"></i> Publish to site
                    </button>
                </form>
            </div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.links.index') }}">Links</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($link->anchor, 40) }}</li>
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
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.links.update', $link) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Site <span class="text-danger">*</span></label>
                            <x-searchable-select name="site_id"
                                :options="$sites->pluck('name', 'id')"
                                :selected="old('site_id', $link->site_id)"
                                placeholder="— Search site —" />
                            @error('site_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title', $link->title) }}" placeholder="Post title">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Link URL <span class="text-danger">*</span></label>
                            <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                                   value="{{ old('url', $link->url) }}" placeholder="https://example.com/page">
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Anchor <span class="text-danger">*</span></label>
                            <input type="text" name="anchor" class="form-control @error('anchor') is-invalid @enderror"
                                   value="{{ old('anchor', $link->anchor) }}" placeholder="Click here">
                            @error('anchor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Type <span class="text-danger">*</span></label>
                            <div class="radio-card-group">
                                <input type="radio" name="type" id="type_post" value="post"
                                       {{ old('type', $link->type) === 'post' ? 'checked' : '' }}>
                                <label for="type_post">
                                    <i class="mdi mdi-file-document-outline"></i> In post
                                </label>

                                <input type="radio" name="type" id="type_homepage" value="homepage"
                                       {{ old('type', $link->type) === 'homepage' ? 'checked' : '' }}>
                                <label for="type_homepage">
                                    <i class="mdi mdi-home-outline"></i> Homepage
                                </label>
                            </div>
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                            <div class="radio-card-group">
                                <input type="radio" name="status" id="status_pending" value="pending"
                                       {{ old('status', $link->status) === 'pending' ? 'checked' : '' }}>
                                <label for="status_pending">
                                    <i class="mdi mdi-clock-outline"></i> Pending
                                </label>

                                <input type="radio" name="status" id="status_published" value="published"
                                       {{ old('status', $link->status) === 'published' ? 'checked' : '' }}>
                                <label for="status_published">
                                    <i class="mdi mdi-check-circle-outline"></i> Published
                                </label>

                                <input type="radio" name="status" id="status_failed" value="failed"
                                       {{ old('status', $link->status) === 'failed' ? 'checked' : '' }}>
                                <label for="status_failed">
                                    <i class="mdi mdi-alert-circle-outline"></i> Failed
                                </label>
                            </div>
                            @error('status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Failed reason</label>
                            <textarea name="failed_reason" class="form-control @error('failed_reason') is-invalid @enderror"
                                      rows="2" placeholder="Error message from the last failed publish attempt">{{ old('failed_reason', $link->failed_reason) }}</textarea>
                            @error('failed_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Check status</label>
                            <div class="radio-card-group">
                                <input type="radio" name="check_status" id="check_status_unknown" value="unknown"
                                       {{ old('check_status', $link->check_status) === 'unknown' ? 'checked' : '' }}>
                                <label for="check_status_unknown">
                                    <i class="mdi mdi-help-circle-outline"></i> Unknown
                                </label>

                                <input type="radio" name="check_status" id="check_status_alive" value="alive"
                                       {{ old('check_status', $link->check_status) === 'alive' ? 'checked' : '' }}>
                                <label for="check_status_alive">
                                    <i class="mdi mdi-check-circle-outline"></i> Alive
                                </label>

                                <input type="radio" name="check_status" id="check_status_not_found" value="not_found"
                                       {{ old('check_status', $link->check_status) === 'not_found' ? 'checked' : '' }}>
                                <label for="check_status_not_found">
                                    <i class="mdi mdi-alert-circle-outline"></i> Not found
                                </label>

                                <input type="radio" name="check_status" id="check_status_blocked" value="blocked"
                                       {{ old('check_status', $link->check_status) === 'blocked' ? 'checked' : '' }}>
                                <label for="check_status_blocked">
                                    <i class="mdi mdi-block-helper"></i> Blocked
                                </label>

                                <input type="radio" name="check_status" id="check_status_compromised" value="compromised"
                                       {{ old('check_status', $link->check_status) === 'compromised' ? 'checked' : '' }}>
                                <label for="check_status_compromised">
                                    <i class="mdi mdi-shield-alert-outline"></i> Compromised
                                </label>
                            </div>
                            @error('check_status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Check error</label>
                            <textarea name="check_error" class="form-control @error('check_error') is-invalid @enderror"
                                      rows="2" placeholder="Error message from the last status check">{{ old('check_error', $link->check_error) }}</textarea>
                            @error('check_error')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Article text <span class="text-danger">*</span></label>
                            <input type="hidden" name="text" id="text-input">
                            <div id="quill-editor" style="height: 320px;"></div>
                            @error('text')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
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

@section('js')
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        const quill = new Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'header': [2, 3, false] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['link'],
                    ['clean'],
                ],
            },
        });

        const initialText = {!! json_encode(old('text', $link->text)) !!};
        quill.clipboard.dangerouslyPasteHTML(initialText);
        document.getElementById('text-input').value = initialText;

        quill.on('text-change', function () {
            document.getElementById('text-input').value = quill.root.innerHTML;
        });
    </script>
@endsection
