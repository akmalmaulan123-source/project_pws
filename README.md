# Parc Fermé

Katalog spesifikasi mobil bertema layar timing balap. Cari dan saring ribuan model mobil, lihat mesin dan generasinya, jelajahi per merek, dan baca ringkasan statistik di halaman Insights. Dibuat dengan Laravel.

> *Parc fermé* adalah area tertutup tempat mobil balap ditahan dan diperiksa setelah sesi. Situs ini meminjam namanya: satu tempat untuk memeriksa setiap mobil beserta angka-angkanya.

## Fitur

- **Beranda** dengan mobil unggulan, statistik katalog, dan model terbaru
- **Cars**: pencarian, filter (merek, negara, bahan bakar, tenaga minimum), 9 pilihan urutan, dan pagination
- **Detail mobil**: generasi, mesin, dan spesifikasi terkuat, plus model lain dari merek yang sama
- **Brands** dan halaman *garage* per merek
- **Insights**: ringkasan statistik katalog
- Saran pencarian saat mengetik (autocomplete) dan pembaruan data otomatis (*live*)
- Halaman error bertema (404, 419, 429, 500, 503)

## Teknologi

PHP 8.3+, Laravel 13, MySQL, Blade, CSS dan JavaScript biasa (tanpa framework frontend).

## Menjalankan di komputer sendiri

```bash
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

1. Nyalakan MySQL (misalnya lewat XAMPP) dan buat database kosong bernama `parc_ferme`.
2. Cek bagian `DB_*` di `.env`, sesuaikan username dan password bila perlu.
3. Siapkan tabel dan isi data:

```bash
php artisan migrate
php artisan cars:import
php artisan cars:import-prices      # opsional: harga dari database/data/prices.csv
php artisan serve
```

Buka `http://127.0.0.1:8000`.

> **Catatan Windows:** `php artisan serve` hanya memproses satu request sekali. Kalau MySQL macet, semua halaman ikut menggantung. Itu sebabnya `.env.example` memakai `SESSION_DRIVER=file` dan `CACHE_STORE=file`, supaya sesi dan cache tidak bergantung pada database.

## Perintah artisan

| Perintah | Fungsi |
| --- | --- |
| `cars:import [--fresh]` | Impor katalog dari CSV di `database/data` (`--fresh` mengosongkan tabel dulu) |
| `cars:import-prices` | Isi harga mesin (USD) dari CSV |
| `brands:fetch-logos` | Ambil logo merek dari Wikimedia |
| `cars:fetch-images` | Ambil foto mobil dari Wikipedia |
| `cars:link-images` | Hubungkan foto yang sudah diunduh ke model |
| `cars:cutout` | Buat PNG transparan untuk mobil di hero |

Perintah yang mengambil data dari Wikipedia/Wikimedia mengirim kontak di User-Agent. Isi `SCRAPER_CONTACT` di `.env` dengan email kamu.

## Pengaturan penting di `.env`

| Variabel | Arti |
| --- | --- |
| `SHOW_CAR_IMAGES` | `false` menampilkan logo merek saja, `true` menampilkan foto mobil |
| `SCRAPER_CONTACT` | Email kontak untuk User-Agent saat mengambil data |
| `USD_TO_IDR` | Kurs tetap untuk estimasi harga |
| `APP_DEBUG` | Matikan (`false`) sebelum situs dipublikasikan |

## Tes

```bash
php artisan test
```

## Struktur singkat

```
app/Http/Controllers   Home, Car, Brand, Insights, Search, Live
app/Models             Brand, CarModel, Generation, Engine
app/Support            CarDataImporter, Fuel
app/Console/Commands   Perintah impor dan pengambilan gambar
resources/views        Blade: layouts, partials, errors, halaman
public/css, public/js  Gaya dan skrip (app.css, polish.css, live.css, ...)
database/data          Sumber data CSV (lihat LICENSE-DATA.txt)
```

## Pengembang

Dwi Akmal Maulana (NIM 282102719)

Data katalog berasal dari dataset pihak ketiga, lisensinya ada di `database/data/LICENSE-DATA.txt`. Kerangka aplikasi: Laravel, berlisensi MIT.
