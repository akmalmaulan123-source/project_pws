@extends('layouts.app')

@section('title', 'About')
@section('description', 'What Parc Fermé is, how to read its numbers, where the data comes from, and how accurate it is.')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/about.css') }}?v={{ filemtime(public_path('css/about.css')) }}">
@endpush

@section('content')
<div class="wrap page about" data-static>

    <header class="ab-head">
        <p class="ab-eyebrow">About</p>
        <h1>About {{ config('app.name') }}</h1>
        <p class="lede">
            In motorsport, <em>parc fermé</em> is the closed area where cars are held and inspected after a session.
            This site borrows the name for a simple idea: one place where you can look over every car, with its numbers laid out clearly.
        </p>
    </header>

    @if ($stats)
        <dl class="ab-stats" aria-label="Catalogue size">
            <div><dt>Brands</dt><dd>{{ number_format($stats['brands']) }}</dd></div>
            <div><dt>Models</dt><dd>{{ number_format($stats['models']) }}</dd></div>
            <div><dt>Generations</dt><dd>{{ number_format($stats['generations']) }}</dd></div>
            <div><dt>Engines</dt><dd>{{ number_format($stats['engines']) }}</dd></div>
        </dl>
    @endif

    <section class="ab-sec" aria-labelledby="ab-do">
        <h2 id="ab-do">What you can do here</h2>
        <div class="ab-cards">
            <a class="ab-card" href="{{ route('cars.index') }}">
                <h3>Find a car</h3>
                <p>Search by name and filter by brand, country, fuel, and minimum power. Sort by power, acceleration, top speed, or age.</p>
                <span aria-hidden="true">Browse cars &rarr;</span>
            </a>
            <a class="ab-card" href="{{ route('brands.index') }}">
                <h3>Explore a brand</h3>
                <p>Open a brand to see every model it makes, then step into its garage to look at each car up close.</p>
                <span aria-hidden="true">See brands &rarr;</span>
            </a>
            <a class="ab-card" href="{{ route('insights') }}">
                <h3>Read the big picture</h3>
                <p>Insights shows models per brand, the fuel mix, and how power changes by country and by decade.</p>
                <span aria-hidden="true">Open Insights &rarr;</span>
            </a>
        </div>
        <p class="ab-note">Open any car to see its generations and every engine variant.</p>
    </section>

    <section class="ab-sec" aria-labelledby="ab-read">
        <h2 id="ab-read">How to read the numbers</h2>
        <dl class="ab-gloss">
            <dt>Power <small>hp</small></dt>
            <dd>Peak engine output in horsepower. More is stronger.</dd>

            <dt>Torque <small>Nm</small></dt>
            <dd>The twisting force the engine delivers, in newton metres. It is what you feel as pull when accelerating.</dd>

            <dt>0 to 100 km/h <small>s</small></dt>
            <dd>Seconds to reach 100 km/h from a standstill. Lower is quicker.</dd>

            <dt>Top speed <small>km/h</small></dt>
            <dd>The highest speed the manufacturer claims.</dd>

            <dt>Economy <small>L/100 km</small></dt>
            <dd>Litres of fuel used per 100 km. Lower uses less fuel.</dd>

            <dt>Fuel</dt>
            <dd>
                Each fuel type wears a colour borrowed from racing tyre compounds.
                <ul class="ab-fuels">
                    <li>@include('partials.fuel-tag', ['fuel' => 'Gasoline'])</li>
                    <li>@include('partials.fuel-tag', ['fuel' => 'Diesel'])</li>
                    <li>@include('partials.fuel-tag', ['fuel' => 'Hybrid'])</li>
                    <li>@include('partials.fuel-tag', ['fuel' => 'Electric'])</li>
                    <li>@include('partials.fuel-tag', ['fuel' => 'Other'])</li>
                </ul>
            </dd>

            <dt>Purple</dt>
            <dd>The best value in a comparison, like the fastest sector on a timing screen: <span class="ab-best">purple</span> marks the leader.</dd>
        </dl>
    </section>

    <section class="ab-sec" aria-labelledby="ab-data">
        <h2 id="ab-data">Where the data comes from</h2>
        <p>
            Brands, models, generations, and engine specifications come from the open
            <a href="https://github.com/gor3a/vehicle-makes-models" rel="noopener">vehicle-makes-models</a> dataset,
            which compiles them from <a href="https://www.autoevolution.com" rel="noopener">autoevolution.com</a>.
            The dataset is released under the
            <a href="https://opendatacommons.org/licenses/odbl/1-0/" rel="noopener">Open Database License (ODbL) 1.0</a>,
            and this site's copy of the data is licensed under the same terms.
        </p>
        <p>
            The source data was collected by scraping, so a small share of values were obviously wrong, for example a car 18 meters wide.
            Values outside a plausible range were cleared when the data was imported and are shown as a dash. This is the only change made to the data.
            Other figures may still be inaccurate.
        </p>
        <p>
            Prices are not part of the dataset. Where a price is shown, it was added separately in US dollars, with a rough Rupiah estimate at a fixed rate.
            A car with no price shows “Not listed”.
        </p>
    </section>

    <section class="ab-sec" aria-labelledby="ab-img">
        <h2 id="ab-img">Logos and photos</h2>
        <p>
            Brand logos were collected from Wikipedia and Wikimedia Commons. Logos are trademarks of their owners and appear here only to identify each brand.
        </p>
        @if (config('cars.show_images'))
            <p>
                Car photos come from Wikipedia and Wikimedia Commons. Each photo is credited to its author on the car's page, under its original licence.
            </p>
        @else
            <p>
                Car photos are not shown right now, so brand logos stand in for them.
            </p>
        @endif
    </section>

    <section class="ab-sec" aria-labelledby="ab-faq">
        <h2 id="ab-faq">Questions you might have</h2>
        <div class="ab-faq">
            <details>
                <summary>Why do some values show a dash?</summary>
                <p>Either the source has no figure for that engine, or the figure was outside a plausible range and was cleared during import.</p>
            </details>
            <details>
                <summary>Why does a car have no price?</summary>
                <p>The dataset has no prices. Only some engines have one added, so most cars show “Not listed”.</p>
            </details>
            <details>
                <summary>Does the site update by itself?</summary>
                <p>Yes. Pages check every few seconds whether the catalogue changed and refresh their content without a reload.</p>
            </details>
            <details>
                <summary>Can I rely on these numbers to buy a car?</summary>
                <p>No. This is a learning project. Figures differ by market, model year, and trim, so confirm with the manufacturer before deciding anything.</p>
            </details>
        </div>
    </section>

    <section class="ab-sec" aria-labelledby="ab-built">
        <h2 id="ab-built">Built with</h2>
        <ul class="ab-chips">
            <li>Laravel</li>
            <li>PHP</li>
            <li>MySQL</li>
            <li>Blade templates</li>
            <li>Plain CSS and JavaScript</li>
        </ul>
        <p>
            Type set in Hanken Grotesk, Saira Condensed, and Orbitron.
            Designed and developed by <strong>Dwi Akmal Maulana</strong> (NIM 282102719), as a student project for a web services course.
        </p>
    </section>

</div>
@endsection
