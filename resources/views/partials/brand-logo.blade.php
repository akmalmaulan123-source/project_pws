{{--
    Logo merek; bila belum ada atau gagal dimuat, tampil kode 3 huruf.
    Butuh: $brand (Brand) ATAU $logo + $code + $hue (untuk data berbentuk array).
    Opsional: $size = 'sm' (44px) | 'md' (56px, default) | 'lg' (84px)
--}}
@php
    if (isset($brand)) {
        $logo = $brand->logo_src;
        $code = $brand->code;
        $hue = $brand->hue;
    }
    $logo = $logo ?? null;
    $size = $size ?? 'md';
@endphp
<span class="brand-logo brand-logo--{{ $size }} {{ $logo ? '' : 'is-broken' }}" style="--h: {{ $hue }}" aria-hidden="true">
    @if ($logo)
        <img src="{{ $logo }}" alt="" loading="lazy" decoding="async" onerror="this.parentNode.classList.add('is-broken')">
    @endif
    <b class="logo-fallback">{{ $code }}</b>
</span>
