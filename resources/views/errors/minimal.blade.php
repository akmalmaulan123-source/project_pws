<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0d0d0e">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/polish.css') }}?v={{ filemtime(public_path('css/polish.css')) }}">
</head>
{{-- Halaman error mandiri: tidak memakai layout utama, session, atau database, supaya tetap tampil saat sistem bermasalah. --}}
<body class="err-body">
<main class="err err--@yield('tone', 'silver')">
    <div class="err-flag" aria-hidden="true"></div>
    <p class="err-code">@yield('code')</p>
    <h1>@yield('heading')</h1>
    <p class="err-text">@yield('message')</p>
    <p class="err-actions">
        <a class="btn" href="{{ url('/') }}">Back to home</a>
        @yield('extra')
    </p>
</main>
</body>
</html>
