@extends('layouts.admin')

@section('title', 'Edit Site')

@section('styles')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
@endsection

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
            <div class="col-auto ms-auto text-end mt-n1">
                <form action="{{ route('admin.sites.fetch_homepage_content', $site) }}" method="POST"
                      class="d-inline ajax-quiet-form" data-reload="1200">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary me-2">
                        <i class="mdi mdi-cloud-download-outline"></i> Fetch current content
                    </button>
                </form>
                <form action="{{ route('admin.sites.replace_content', $site) }}" method="POST" class="d-inline ajax-quiet-form">
                    @csrf
                    <button type="submit" class="btn btn-primary"
                            @if(!$site->homepage_content) disabled title="Fill in and save homepage content first" @endif>
                        <i class="mdi mdi-file-replace-outline"></i> Replace content
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

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Homepage content</label>
                                <button type="button" id="toggle-code-view" class="btn btn-sm btn-outline-secondary">
                                    <i class="mdi mdi-code-tags"></i> View code
                                </button>
                            </div>
                            <input type="hidden" name="homepage_content" id="text-input">
                            <div id="quill-wrapper">
                                <div id="quill-editor" style="height: 320px;"></div>
                            </div>
                            <textarea id="html-source" class="form-control d-none" rows="14" style="font-family: monospace; font-size: 0.85rem;"></textarea>
                            @error('homepage_content')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Mirrors what's currently live on the site's homepage — updated automatically by the "Replace content" action.</div>
                        </div>

                        @php
                            $toRadioValue = fn ($value) => $value === null ? '' : ($value ? '1' : '0');
                            $isActiveValue = old('is_active', $toRadioValue($site->is_active));
                            $isAutoValue = old('is_auto', $toRadioValue($site->is_auto));
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
                            <label class="form-label d-block">Mode</label>
                            <div class="radio-card-group">
                                <input type="radio" name="is_auto" id="is_auto_unknown" value=""
                                       {{ $isAutoValue === '' ? 'checked' : '' }}>
                                <label for="is_auto_unknown">
                                    <i class="mdi mdi-help-circle-outline"></i> Unknown
                                </label>

                                <input type="radio" name="is_auto" id="is_auto_yes" value="1"
                                       {{ $isAutoValue === '1' ? 'checked' : '' }}>
                                <label for="is_auto_yes">
                                    <i class="mdi mdi-check-circle-outline"></i> Auto
                                </label>

                                <input type="radio" name="is_auto" id="is_auto_no" value="0"
                                       {{ $isAutoValue === '0' ? 'checked' : '' }}>
                                <label for="is_auto_no">
                                    <i class="mdi mdi-close-circle-outline"></i> Manual
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

        const initialText = {!! json_encode(old('homepage_content', $site->homepage_content)) !!};
        quill.clipboard.dangerouslyPasteHTML(initialText);
        document.getElementById('text-input').value = initialText;

        quill.on('text-change', function () {
            document.getElementById('text-input').value = quill.root.innerHTML;
        });

        const codeToggleBtn = document.getElementById('toggle-code-view');
        const htmlSource = document.getElementById('html-source');
        const quillWrapper = document.getElementById('quill-wrapper');
        const textInput = document.getElementById('text-input');
        let codeViewActive = false;

        codeToggleBtn.addEventListener('click', function () {
            if (!codeViewActive) {
                htmlSource.value = textInput.value;
                quillWrapper.classList.add('d-none');
                htmlSource.classList.remove('d-none');
                codeToggleBtn.innerHTML = '<i class="mdi mdi-eye-outline"></i> Visual editor';
            } else {
                textInput.value = htmlSource.value;
                quill.setContents(quill.clipboard.convert(htmlSource.value));
                quillWrapper.classList.remove('d-none');
                htmlSource.classList.add('d-none');
                codeToggleBtn.innerHTML = '<i class="mdi mdi-code-tags"></i> View code';
            }
            codeViewActive = !codeViewActive;
        });

        htmlSource.addEventListener('input', function () {
            textInput.value = htmlSource.value;
        });
    </script>
@endsection
