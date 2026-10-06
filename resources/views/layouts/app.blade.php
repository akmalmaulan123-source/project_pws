<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title'){{ config('app.name') }} - @yield('title')@else{{ config('app.name') }}@endif</title>
    <meta name="description" content="@yield('description', 'Specs for thousands of car models and engines from brands around the world. Search, filter and compare.')">
    <meta name="theme-color" content="#0d0d0e">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Saira+Condensed:ital,wght@0,500;0,700;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body id="top">
<a class="skip" href="#main">Skip to content</a>

<header class="top">
    <div class="wrap top-in">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ config('app.name') }} home">
            <span class="brand-flag" aria-hidden="true"></span>
            <span class="brand-name">{{ config('app.name') }}</span>
        </a>
        <nav class="nav" aria-label="Main">
            <a href="{{ route('cars.index') }}" @class(['is-on' => request()->routeIs('cars.*')])>Cars</a>
            <a href="{{ route('brands.index') }}" @class(['is-on' => request()->routeIs('brands.*')])>Brands</a>
            <a href="{{ route('compare') }}" @class(['is-on' => request()->routeIs('compare')])>Compare</a>
            <a href="{{ route('about') }}" @class(['is-on' => request()->routeIs('about')])>About</a>
        </nav>
        <form class="top-search" action="{{ route('cars.index') }}" method="get" role="search">
            <label class="sr-only" for="top-q">Search cars</label>
            <input id="top-q" name="q" type="search" placeholder="Search cars" data-suggest data-url="{{ route('search.suggest') }}" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-haspopup="listbox" value="{{ request()->routeIs('cars.index') && is_string(request('q')) ? request('q') : '' }}" autocomplete="off">
        </form>
    </div>
</header>

<main id="main">
    @yield('content')
</main>

<footer class="foot">
    <div class="foot-flag" aria-hidden="true"></div>
    <div class="wrap foot-in">
        <div class="foot-grid">
            <section class="foot-about" aria-label="About {{ config('app.name') }}">
                <a class="brand" href="{{ route('home') }}" aria-label="{{ config('app.name') }} home">
                    <span class="brand-flag" aria-hidden="true"></span>
                    <span class="brand-name">{{ config('app.name') }}</span>
                </a>
                <p class="foot-lead">Specs for thousands of car models and engines from brands around the world, laid out clearly in one place. Search, filter and compare, race-timing style.</p>
                <p class="foot-tag">A student project for a web services course, built with Laravel.</p>
            </section>

            <div class="foot-notes">
                <section class="foot-col" aria-label="What you can do">
                    <h2>What you can do</h2>
                    <p>Browse cars by brand, country, fuel and power. Open a car to see its generations and every engine variant, then pick up to four engines and compare them side by side.</p>
                </section>

                <section class="foot-col" aria-label="About the name">
                    <h2>Why the name</h2>
                    <p>In motorsport, <em>parc ferm&eacute;</em> is the closed area where cars are held and inspected after a session. This site borrows the name for a simple idea: one place to look over every car, with its numbers on show.</p>
                </section>
            </div>
        </div>

        <div class="foot-bar">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
            <a class="foot-top" href="#top">Back to top <span aria-hidden="true">&uarr;</span></a>
        </div>
    </div>
</footer>

<aside id="compare-tray" class="tray" data-url="{{ route('compare') }}" data-max="4" hidden aria-label="Engines selected for comparison"></aside>

<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
<script src="{{ asset('js/search.js') }}?v={{ filemtime(public_path('js/search.js')) }}" defer></script>
<script src="{{ asset('js/motion.js') }}?v={{ filemtime(public_path('js/motion.js')) }}" defer></script>
</body>
</html>
