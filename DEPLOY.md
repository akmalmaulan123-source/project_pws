# Panduan deploy Parc Fermé

Daftar periksa untuk memasang situs ini di server (hosting atau VPS). Semua perintah dijalankan di folder project.

## 1. Persiapan server

- PHP 8.3 atau lebih baru dengan ekstensi umum Laravel (`mbstring`, `openssl`, `pdo_mysql`, `ctype`, `json`, `tokenizer`, `xml`, `fileinfo`)
- MySQL/MariaDB, Composer, dan HTTPS (sertifikat gratis dari Let's Encrypt cukup)
- **Document root harus mengarah ke folder `public/`**, bukan ke root project. Kalau tidak, `.env` bisa terunduh orang lain.

## 2. Pasang kode

```bash
composer install --no-dev --optimize-autoloader
cp .env.production.example .env      # lalu isi nilainya
php artisan key:generate
php artisan migrate --force
php artisan cars:import              # isi katalog dari database/data
```

## 3. Percepat Laravel (hanya di produksi)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Setelah mengubah `.env`, rute, atau view, jalankan `php artisan optimize:clear` lalu ulangi empat perintah di atas.

## 4. Izin folder

Folder `storage/` dan `bootstrap/cache/` harus bisa ditulis oleh user web server.

```bash
chmod -R ug+rwX storage bootstrap/cache
```

## 5. Periksa sebelum dibuka ke publik

- [ ] `APP_DEBUG=false` dan `APP_ENV=production`
- [ ] `SESSION_SECURE_COOKIE=true` dan situs diakses lewat `https://`
- [ ] Buka `https://domainmu/.env` dan `https://domainmu/storage/logs/laravel.log`: keduanya harus **404 atau 403**
- [ ] Buka alamat ngawur: harus muncul halaman 404 bertema, bukan halaman debug Laravel
- [ ] Header keamanan aktif (cek di DevTools > Network > Response Headers)
- [ ] `/__dev-reload` mengembalikan 404 (rute itu hanya ada saat `APP_ENV=local`)
- [ ] Password database bukan kosong dan user-nya bukan `root`

## 6. Perawatan

- Log harian ada di `storage/logs/`. Hapus yang lama secara berkala, atau biarkan `LOG_STACK=daily` yang merotasinya.
- Setelah impor atau pembaruan data, cache katalog dibuang otomatis (lihat `app/Support/CatalogCache.php`).
