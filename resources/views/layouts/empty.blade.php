<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Football Champions</title>

    @vite([
        'resources/assets/admin/vendors/mdi/css/materialdesignicons.min.css',
        'resources/assets/admin/css/styles.css'
    ])

    <link rel="icon" type="image/png" href="{{ asset('/build/images/favicon.ico') }}"/>
</head>
<body>
<div class="container-scroller">

    @yield('content')

</div>

@vite(['resources/assets/admin/js/app.js'])

</body>
</html>
