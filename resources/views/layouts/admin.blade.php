<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title') - Football Champions</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @vite([
        'resources/assets/admin/vendors/mdi/css/materialdesignicons.min.css',
        'resources/assets/admin/css/styles.css',
        'resources/assets/admin/css/custom.css'
    ])

    <link rel="icon" type="image/png" href="{{ asset('/build/images/favicon.ico') }}"/>
</head>
<body @class(['sidebar-icon-only' => request()->cookie('sidebar-status') === 'true'])>
    <div class="container-scroller">

        <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                <a class="navbar-brand brand-logo" href="/">
                    <img src="{{ asset('/build/images/logo.svg') }}" alt="logo"/>
                </a>
                <a class="navbar-brand brand-logo-mini" href="/">
                    <img src="{{ asset('/build/images/logo-mini.svg') }}" alt="logo"/>
                </a>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-stretch">
                <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
                    <span class="mdi mdi-menu"></span>
                </button>
                @include('admin.partials.navbar')
            </div>
        </nav>

        <div class="container-fluid page-body-wrapper">
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                @include('admin.partials.menu')
            </nav>

            <div class="main-panel">
                <div class="content-wrapper @yield('wrapper')">
                    @yield('content')
                </div>

                @include('admin.partials.footer')
            </div>
        </div>
    </div>

@vite(['resources/assets/admin/js/app.js'])

@yield('js')

</body>
</html>
