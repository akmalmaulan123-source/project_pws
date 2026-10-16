{{-- Font dimuat sekali di sini; dipakai layout utama dan halaman garage. Tampil dulu dengan font cadangan, ganti saat font siap. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Font dimuat tanpa memblokir: halaman tampil langsung dengan font cadangan, lalu berganti saat font siap.
         (Stylesheet font eksternal yang biasa memblokir tampilan; kalau koneksi ke Google Fonts lambat, pindah halaman ikut tertahan.) --}}
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Orbitron:wght@900&family=Saira+Condensed:ital,wght@0,500;0,700;1,600;1,700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Orbitron:wght@900&family=Saira+Condensed:ital,wght@0,500;0,700;1,600;1,700&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Orbitron:wght@900&family=Saira+Condensed:ital,wght@0,500;0,700;1,600;1,700&display=swap"></noscript>
