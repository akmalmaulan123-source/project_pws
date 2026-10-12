{{--
    Dial panel instrumen. Butuh: $value (0 sampai 1), $num, $unit, $cap, $sub; opsional $big, $count dan $suffix (untuk animasi hitung naik).
    Busur 270 derajat, radius 66: panjang busur penuh 311, keliling 414,7.
--}}
@php
    $big = $big ?? false;
    $size = $big ? 300 : 230;
    $len = round(max(0, min(1, $value)) * 311, 1);
@endphp
<div class="ins-dial {{ $big ? 'is-big' : '' }}">
    <svg viewBox="0 0 200 200" width="{{ $size }}" height="{{ $size }}" role="img" aria-label="{{ $cap }}: {{ $num }} {{ $unit }}">
        @for ($k = 0; $k <= 20; $k++)
            @php $a = deg2rad(135 + 13.5 * $k); $major = $k % 5 === 0; $r1 = $major ? 78 : 82; @endphp
            <line class="{{ $major ? 'tm' : 'tn' }}" style="--k: {{ $k }}" x1="{{ round(100 + $r1 * cos($a), 2) }}" y1="{{ round(100 + $r1 * sin($a), 2) }}" x2="{{ round(100 + 92 * cos($a), 2) }}" y2="{{ round(100 + 92 * sin($a), 2) }}"/>
        @endfor
        <g transform="rotate(135 100 100)" fill="none" stroke-linecap="round" stroke-width="{{ $big ? 7 : 6 }}">
            <circle class="trk" cx="100" cy="100" r="66" stroke-dasharray="311 414.7"/>
            <circle class="val" cx="100" cy="100" r="66" stroke-dasharray="{{ $len }} 414.7" style="--len: {{ $len }}"/>
        </g>
        <text class="ins-dn" @isset($count) data-count="{{ $count }}" data-suffix="{{ $suffix ?? '' }}" data-delay="{{ $big ? 450 : 750 }}" data-dur="1700" @endisset x="100" y="{{ $big ? 104 : 102 }}" text-anchor="middle" style="font-size: {{ $big ? 46 : 36 }}px">{{ $num }}</text>
        <text class="ins-du" x="100" y="{{ $big ? 124 : 120 }}" text-anchor="middle" letter-spacing="3">{{ $unit }}</text>
    </svg>
    <div class="ins-dcap">{{ $cap }}</div>
    <div class="ins-dsub">{{ $sub }}</div>
</div>
