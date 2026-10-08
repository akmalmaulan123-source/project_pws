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
    {{-- Font dimuat tanpa memblokir: halaman tampil langsung dengan font cadangan, lalu berganti saat font siap.
         (Stylesheet font eksternal yang biasa memblokir tampilan; kalau koneksi ke Google Fonts lambat, pindah halaman ikut tertahan.) --}}
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Orbitron:wght@900&family=Saira+Condensed:ital,wght@0,500;0,700;1,600;1,700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Orbitron:wght@900&family=Saira+Condensed:ital,wght@0,500;0,700;1,600;1,700&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Orbitron:wght@900&family=Saira+Condensed:ital,wght@0,500;0,700;1,600;1,700&display=swap"></noscript>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/live.css') }}?v={{ filemtime(public_path('css/live.css')) }}">
    <script>document.documentElement.classList.add('js');</script>
<script type="speculationrules">
{
    "prefetch": [{
        "where": { "and": [
            { "href_matches": "/*" },
            { "not": { "href_matches": ["{{ parse_url(route('live'), PHP_URL_PATH) }}*", "{{ parse_url(route('search.suggest'), PHP_URL_PATH) }}*", "/__dev*"] } },
            { "not": { "selector_matches": "[download], [target=_blank]" } }
        ] },
        "eagerness": "moderate"
    }]
}
</script>
</head>
<body id="top" @class(['has-hero' => request()->routeIs('home')])>
<a class="skip" href="#main">Skip to content</a>

<header class="top" id="site-top">
    <div class="wrap top-in">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ config('app.name') }} home">
            <svg class="brand-mark" viewBox="0 0 52 40" aria-hidden="true" focusable="false">
                <defs><linearGradient id="bm-g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f7f7f7"/><stop offset="1" stop-color="#a8a8a8"/></linearGradient></defs>
                <g transform="translate(4 3) skewX(-14)" fill="none" stroke="url(#bm-g)" stroke-width="7" stroke-linejoin="miter"><path d="M10 34V4h14a9 9 0 0 1 0 18H10"/></g>
                <g transform="translate(4 3) skewX(-14)" fill="url(#bm-g)"><rect x="32" y="28" width="6" height="6"/><rect x="41" y="28" width="6" height="6" opacity=".55"/></g>
            </svg>
            <span class="brand-name">{{ config('app.name') }}</span>
        </a>
        <nav class="nav nav--segment" aria-label="Main">
            <a href="{{ route('cars.index') }}" @class(['is-on' => request()->routeIs('cars.*')])>Cars</a>
            <a href="{{ route('brands.index') }}" @class(['is-on' => request()->routeIs('brands.*')])>Brands</a>
            <a href="{{ route('compare') }}" @class(['is-on' => request()->routeIs('compare')])>Compare</a>
            <a href="{{ route('about') }}" @class(['is-on' => request()->routeIs('about')])>About</a>
        </nav>
        <form class="top-search search--line" action="{{ route('cars.index') }}" method="get" role="search">
            <label class="sr-only" for="top-q">Search cars</label>
            <input id="top-q" name="q" type="search" placeholder="Search cars" data-suggest data-url="{{ route('search.suggest') }}" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-haspopup="listbox" value="{{ request()->routeIs('cars.index') && is_string(request('q')) ? request('q') : '' }}" autocomplete="off">
        </form>
        <button class="live-pill" id="live-pill" type="button" hidden data-url="{{ route('live') }}" data-interval="8000" data-state="on" aria-pressed="true">
            <span class="live-dot" aria-hidden="true"></span><span class="live-text">Live</span>
        </button>
    </div>
</header>

<main id="main">
    @yield('content')
</main>

<footer class="foot">
    <div class="foot-flag" aria-hidden="true"></div>
    <div class="wrap foot-in">
        {{-- Nama situs raksasa (juga tautan ke beranda): kerangka huruf, lalu terisi dari kiri ke kanan saat footer terlihat --}}
        <a class="foot-wm rv" style="--i: 0" href="{{ route('home') }}" data-t="{{ config('app.name') }}" aria-label="{{ config('app.name') }} home">{{ config('app.name') }}</a>

        <nav class="foot-links rv" style="--i: 1" aria-label="Footer">
            <a href="{{ route('cars.index') }}" @class(['is-on' => request()->routeIs('cars.*')])><span>Cars</span></a>
            <a href="{{ route('brands.index') }}" @class(['is-on' => request()->routeIs('brands.*')])><span>Brands</span></a>
            <a href="{{ route('compare') }}" @class(['is-on' => request()->routeIs('compare')])><span>Compare</span></a>
            <a href="{{ route('about') }}" @class(['is-on' => request()->routeIs('about')])><span>About</span></a>
        </nav>

        <div class="foot-notes rv" style="--i: 2">
            <p class="foot-lead">Specs for thousands of car models and engines from brands around the world, laid out clearly in one place. Search, filter and compare, race-timing style.</p>
            <p>In motorsport, <em>parc ferm&eacute;</em> is the closed area where cars are held and inspected after a session. This site borrows the name for a simple idea: one place to look over every car, with its numbers on show.</p>
        </div>

        <div class="foot-bar rv" style="--i: 3">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }} &middot; Designed &amp; developed by <strong>Dwi Akmal Maulana</strong> (NIM 282102719) &middot; Built with Laravel</span>
            <a class="foot-top" href="#top"><span>Back to top</span><b aria-hidden="true">&uarr;</b></a>
        </div>
    </div>
</footer>

<aside id="compare-tray" class="tray" data-url="{{ route('compare') }}" data-max="4" hidden aria-label="Engines selected for comparison"></aside>

<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
<script src="{{ asset('js/search.js') }}?v={{ filemtime(public_path('js/search.js')) }}" defer></script>
<script src="{{ asset('js/motion.js') }}?v={{ filemtime(public_path('js/motion.js')) }}" defer></script>
<script src="{{ asset('js/fast.js') }}?v={{ filemtime(public_path('js/fast.js')) }}" defer></script>
<script src="{{ asset('js/live.js') }}?v={{ filemtime(public_path('js/live.js')) }}" defer></script>
<script>
/* Footer: animasi masuk dimulai saat footer terlihat (tanpa animasi bila reduce motion) */
(function () {
    var f = document.querySelector('.foot');
    if (!f) { return; }
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) { f.classList.add('is-in'); return; }
    var io = new IntersectionObserver(function (es) {
        if (es[0].isIntersecting) { f.classList.add('is-in'); io.disconnect(); }
    }, { threshold: 0.2 });
    io.observe(f);
})();
</script>
<script>
(function () {
    var h = document.getElementById('site-top');
    if (!h) { return; }
    var on = false;
    function check() {
        var s = (window.pageYOffset || document.documentElement.scrollTop) > 12;
        if (s !== on) { on = s; h.classList.toggle('is-scrolled', s); }
    }
    check();
    window.addEventListener('scroll', check, { passive: true });
})();
</script>
@if (app()->environment('local'))
<script src="{{ asset('js/devreload.js') }}" data-url="{{ route('dev.reload') }}" data-interval="2500" defer></script>
@endif
</body>
</html>
