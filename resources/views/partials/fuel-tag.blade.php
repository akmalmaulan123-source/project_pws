@if ($fuel)
    <span class="fuel {{ \App\Support\Fuel::css($fuel) }}">{{ $fuel }}</span>
@endif
