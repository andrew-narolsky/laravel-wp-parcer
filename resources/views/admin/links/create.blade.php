@extends('layouts.admin')

@section('title', 'Add Link')

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
                    </span> New link
                </h3>
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
                <li class="breadcrumb-item active" aria-current="page">New link</li>
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
                    <form action="{{ route('admin.links.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Site <span class="text-danger">*</span></label>
                            <x-searchable-select name="site_id"
                                :options="$sites->pluck('name', 'id')"
                                :selected="old('site_id')"
                                placeholder="— Search site —" />
                            @error('site_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}" placeholder="Post title">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Link URL <span class="text-danger">*</span></label>
                            <input type="text" name="url" class="form-control @error('url') is-invalid @enderror"
                                   value="{{ old('url') }}" placeholder="https://example.com/page or /">
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Anchor <span class="text-danger">*</span></label>
                            <input type="text" name="anchor" class="form-control @error('anchor') is-invalid @enderror"
                                   value="{{ old('anchor') }}" placeholder="Click here">
                            @error('anchor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Type <span class="text-danger">*</span></label>
                            <div class="radio-card-group">
                                <input type="radio" name="type" id="type_post" value="post"
                                       {{ old('type') === 'post' ? 'checked' : '' }}>
                                <label for="type_post">
                                    <i class="mdi mdi-file-document-outline"></i> In post
                                </label>

                                <input type="radio" name="type" id="type_homepage" value="homepage"
                                       {{ old('type') === 'homepage' ? 'checked' : '' }}>
                                <label for="type_homepage">
                                    <i class="mdi mdi-home-outline"></i> Homepage
                                </label>
                            </div>
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
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

        const oldText = {!! json_encode(old('text', '')) !!};
        if (oldText) {
            quill.root.innerHTML = oldText;
            document.getElementById('text-input').value = oldText;
        }

        quill.on('text-change', function () {
            document.getElementById('text-input').value = quill.root.innerHTML;
        });
    </script>
@endsection
