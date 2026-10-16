<?php

return [
    /*
     | Tampilkan gambar mobil (foto / PNG hasil cars:cutout)?
     | false = semua gambar mobil dikosongkan dan diganti logo merek.
     | Nyalakan lagi nanti dengan SHOW_CAR_IMAGES=true di .env.
     */
    'show_images' => (bool) env('SHOW_CAR_IMAGES', false),

    /*
     | Kontak yang dikirim di User-Agent saat mengambil data dari Wikipedia/Wikimedia (sopan santun API mereka).
     | Isi SCRAPER_CONTACT di .env dengan email kamu; jangan ditulis langsung di kode.
     */
    'contact' => env('SCRAPER_CONTACT') ?: 'contact@example.com',
];
