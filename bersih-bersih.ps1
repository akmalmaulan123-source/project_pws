# bersih-bersih.ps1: menghapus file yang terbukti tidak terpakai di project Parc Ferme.
#
# Cara pakai (jalankan dari root project, folder yang berisi file "artisan"):
#   .\bersih-bersih.ps1          -> hanya MENAMPILKAN apa yang akan dihapus (aman, tidak menghapus apa pun)
#   .\bersih-bersih.ps1 -Apply   -> benar-benar menghapus
#
# Saran: tutup dulu "php artisan serve" (Windows mengunci file log yang sedang dipakai),
# dan commit perubahanmu di git supaya semua bisa dikembalikan.

param([switch]$Apply)

if (-not (Test-Path -LiteralPath 'artisan')) {
    Write-Host 'Jalankan skrip ini dari root project (folder yang berisi file artisan).' -ForegroundColor Red
    exit 1
}

# Semua di daftar ini sudah dicek: tidak dirujuk oleh kode, view, JS, CSS, atau perintah artisan.
$targets = @(
    # Partial Blade yang tidak pernah di-include
    'resources\views\partials\car-cards.blade.php',
    'resources\views\partials\car-silhouette.blade.php',
    'resources\views\partials\generations.blade.php',

    # Draf logo alternatif (tidak dipakai di halaman mana pun)
    'public\images\logo-options',

    # Database SQLite kosong 0 byte (project memakai MySQL)
    'database\database.sqlite',

    # Tes contoh bawaan Laravel yang sudah diganti tes sungguhan
    'tests\Feature\ExampleTest.php',
    'tests\Unit\ExampleTest.php',

    # Petunjuk pemasangan lama (sudah dikerjakan)
    'BACA-DULU.txt',
    'CARA-PASANG.txt',
    'CARA-PASANG-2.txt',
    'CARA-PASANG-3.txt',
    'CARA-PASANG-ABOUT.txt',
    'CARA-PASANG-AKUN.txt',

    # Log lama (dibuat ulang otomatis saat ada log baru)
    'storage\logs\laravel.log'
)

foreach ($t in $targets) {
    if (-not (Test-Path -LiteralPath $t)) {
        Write-Host ('(sudah tidak ada)  ' + $t) -ForegroundColor DarkGray
        continue
    }
    if ($Apply) {
        Remove-Item -LiteralPath $t -Recurse -Force
        Write-Host ('dihapus            ' + $t) -ForegroundColor Green
    } else {
        Write-Host ('akan dihapus       ' + $t) -ForegroundColor Yellow
    }
}

if ($Apply) {
    Write-Host ''
    Write-Host 'Selesai. Jalankan berikutnya:  php artisan optimize:clear' -ForegroundColor Cyan
} else {
    Write-Host ''
    Write-Host 'Ini hanya pratinjau. Jalankan dengan -Apply untuk benar-benar menghapus.' -ForegroundColor Cyan
}
