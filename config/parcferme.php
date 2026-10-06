<?php

return [
    // Kurs tetap untuk estimasi harga dalam Rupiah (harga disimpan dalam USD).
    // Ini hanya nilai awal; ubah lewat USD_TO_IDR di .env agar sesuai kurs saat ini.
    'usd_to_idr' => (int) env('USD_TO_IDR', 16000),
];
