@extends('layouts.app')

@section('title', 'About')

@section('content')
<div class="wrap page prose-page">
    <h1>About {{ config('app.name') }}</h1>

    <p>
        In motorsport, <em>parc fermé</em> is the closed area where cars are held and inspected after a session.
        This site borrows the name for a simple idea: one place where you can look over every car, with its numbers laid out clearly.
    </p>

    <h2>What you can do here</h2>
    <ul>
        <li>Search and filter cars by brand, country, fuel, and power.</li>
        <li>Open a car to see its generations and every engine variant.</li>
        <li>Pick up to four engines and compare them side by side.</li>
    </ul>

    <h2>Where the data comes from</h2>
    <p>
        Brands, models, generations, and engine specifications come from the open
        <a href="https://github.com/gor3a/vehicle-makes-models" rel="noopener">vehicle-makes-models</a> dataset,
        released under the <a href="https://opendatacommons.org/licenses/odbl/1-0/" rel="noopener">Open Database License (ODbL) 1.0</a>.
        This site's copy of the data is licensed under the same terms.
    </p>
    <p>
        The source data was collected by scraping, so a small share of values were obviously wrong, for example a car 18 meters wide.
        Values outside a plausible range were cleared when the data was imported, and are shown as a dash. Other figures may still be inaccurate.
    </p>
    <p>
        Prices are not part of the dataset. They are entered by hand for some engines, shown in US dollars with a rough Rupiah estimate at a fixed rate.
        A car with no price shows “Not listed”.
    </p>

    <h2>Built with</h2>
    <p>Laravel, MySQL, and plain HTML and CSS. A student project for a web services course.</p>
</div>
@endsection
