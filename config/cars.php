<?php

return [
    /*
     | Tampilkan gambar mobil (foto / PNG hasil cars:cutout)?
     | false = semua gambar mobil dikosongkan dan diganti logo merek.
     | Nyalakan lagi nanti dengan SHOW_CAR_IMAGES=true di .env.
     */
    'show_images' => (bool) env('SHOW_CAR_IMAGES', false),
];
