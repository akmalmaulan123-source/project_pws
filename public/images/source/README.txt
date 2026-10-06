SATU-SATUNYA tempat foto mobil. Taruh foto asli (dengan background) di folder ini, lalu jalankan:

  php artisan cars:link-images     (pasang foto ke model)
  php artisan cars:cutout --all    (hapus background -> public/images/cars/<id>.png)

Hasil PNG tanpa background itu dipakai di SEMUA tempat: hero beranda, kartu katalog, dan halaman detail.
Folder public/images/stage tidak dipakai lagi.

Nama file dibaca sebagai awalan nama "merek model" (huruf kecil, spasi jadi tanda minus):
  porsche.jpg              -> semua model Porsche (cadangan, paling umum)
  porsche-911.jpg          -> semua model berawalan 911 (911 Carrera, 911 Turbo, ...)
  porsche-911-gt3-rs.jpg   -> hanya 911 GT3 RS
  pagani-huayra.jpg        -> hanya Huayra
  123.jpg                  -> hanya model dengan ID 123

Kalau beberapa file cocok, yang PALING SPESIFIK menang. Jadi kamu bisa mulai dari foto merek,
lalu menambah foto kelompok atau foto per model hanya untuk mobil yang penting.

Contoh merek dengan tanda minus: mercedes-amg.jpg, rolls-royce.jpg, aston-martin.jpg
Format: jpg, jpeg, png, webp.

Daftar kelompok yang belum punya foto + nama filenya:  php artisan cars:link-images --list
Foto yang sama dipotong sekali saja lalu disalin ke tiap model.
Saran foto: mobil utuh dari samping / tiga perempat depan, latar kontras, lebar minimal 1600 px.
