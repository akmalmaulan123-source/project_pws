{{-- Tag untuk mesin pencari dan pratinjau tautan (WhatsApp, Telegram, Discord, dst.) --}}
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="@hasSection('title'){{ config('app.name') }} - @yield('title')@else{{ config('app.name') }}@endif">
<meta property="og:description" content="@yield('description', 'Specs for thousands of car models and engines from brands around the world. Search, filter and explore the numbers.')">
<meta property="og:url" content="{{ url()->current() }}">
<meta name="twitter:card" content="summary">
