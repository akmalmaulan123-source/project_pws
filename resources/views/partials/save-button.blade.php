{{-- Tombol simpan/hapus mobil. Butuh: $carModel; opsional: $saved. Tamu yang menekannya diarahkan ke halaman masuk, lalu kembali ke halaman ini. --}}
@php $saved = $saved ?? false; @endphp
<form class="save-form" method="post" action="{{ route('saved.toggle', $carModel) }}">
    @csrf
    <button class="btn btn-quiet save-btn @if ($saved) is-saved @endif" type="submit" aria-pressed="{{ $saved ? 'true' : 'false' }}">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="{{ $saved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12v18l-6-4-6 4z"/></svg>
        <span>{{ $saved ? 'Saved' : 'Save' }}</span>
    </button>
</form>
