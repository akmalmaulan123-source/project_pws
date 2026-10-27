# bersih-bersih.ps1: pembersihan sekali jalan untuk project Parc Ferme.
#
# Jalankan dari root project (folder yang berisi file "artisan"):
#   .\bersih-bersih.ps1          -> PRATINJAU: hanya menampilkan apa yang akan berubah
#   .\bersih-bersih.ps1 -Apply   -> mengerjakan semuanya
#
# Yang dikerjakan:
#   1. Hapus file tak terpakai (log lama, cache tes, logo duplikat, salinan logo asli)
#   2. Hapus Vite/Tailwind (layout memakai public/css langsung, tidak ada @vite)
#   3. Hapus baris "npm install" dan "npm run build" dari composer.json (script "setup")
#   4. Ubah QUEUE_CONNECTION=database menjadi sync (tidak ada antrean di project ini)
#
# Saran: commit dulu di git agar bisa dikembalikan, dan tutup "php artisan serve".

param([switch]$Apply)

if (-not (Test-Path -LiteralPath 'artisan')) {
    Write-Host 'Jalankan skrip ini dari root project (folder yang berisi file artisan).' -ForegroundColor Red
    exit 1
}

function Say($state, $text, $color) { Write-Host ($state.PadRight(19) + $text) -ForegroundColor $color }

# ---------- 1 + 2. Hapus file ----------
$targets = @(
    'storage\logs\laravel.log',
    '.phpunit.result.cache',
    'public\images\brands\_original',
    # Vite/Tailwind
    'package.json', 'package-lock.json', 'vite.config.js', '.npmrc',
    'resources\css\app.css', 'resources\js\app.js', 'node_modules'
)

# Logo .jpg yang sudah punya versi svg/png/webp tidak pernah dipakai
# (Brand::getLogoSrcAttribute memilih urutan svg, png, webp, jpg).
if (Test-Path -LiteralPath 'public\images\brands') {
    Get-ChildItem -LiteralPath 'public\images\brands' -Filter '*.jpg' -File | ForEach-Object {
        $base = Join-Path $_.DirectoryName $_.BaseName
        foreach ($ext in 'svg', 'png', 'webp') {
            if (Test-Path -LiteralPath ($base + '.' + $ext)) {
                $targets += ('public\images\brands\' + $_.Name)
                break
            }
        }
    }
}

foreach ($t in $targets) {
    if (-not (Test-Path -LiteralPath $t)) { Say '(sudah tidak ada)' $t DarkGray; continue }
    if ($Apply) { Remove-Item -LiteralPath $t -Recurse -Force; Say 'dihapus' $t Green }
    else { Say 'akan dihapus' $t Yellow }
}

# ---------- 3. composer.json: buang langkah npm dari script "setup" ----------
if (Test-Path -LiteralPath 'composer.json') {
    $raw = [IO.File]::ReadAllText((Resolve-Path 'composer.json'))
    $new = $raw -replace '("@php artisan migrate --force"),\s*"npm install --ignore-scripts",\s*"npm run build"', '$1'
    if ($new -eq $raw) {
        Say '(sudah bersih)' 'composer.json' DarkGray
    } else {
        $valid = $true
        try { $null = $new | ConvertFrom-Json } catch { $valid = $false }
        if (-not $valid) {
            Say 'DILEWATI' 'composer.json (hasil edit tidak valid, file tidak diubah)' Red
        } elseif ($Apply) {
            Copy-Item -LiteralPath 'composer.json' -Destination 'composer.json.bak' -Force
            [IO.File]::WriteAllText((Resolve-Path 'composer.json'), $new, (New-Object Text.UTF8Encoding $false))
            Say 'diubah' 'composer.json (cadangan: composer.json.bak)' Green
        } else {
            Say 'akan diubah' 'composer.json (hapus langkah npm dari "setup")' Yellow
        }
    }
}

# ---------- 4. .env: antrean tidak dipakai ----------
foreach ($f in '.env', '.env.example', '.env.production.example') {
    if (-not (Test-Path -LiteralPath $f)) { continue }
    $raw = [IO.File]::ReadAllText((Resolve-Path $f))
    $new = $raw -replace '(?m)^QUEUE_CONNECTION=database', 'QUEUE_CONNECTION=sync'
    if ($new -eq $raw) { Say '(sudah sync)' $f DarkGray; continue }
    if ($Apply) {
        [IO.File]::WriteAllText((Resolve-Path $f), $new, (New-Object Text.UTF8Encoding $false))
        Say 'diubah' ($f + '  (QUEUE_CONNECTION=sync)') Green
    } else {
        Say 'akan diubah' ($f + '  (QUEUE_CONNECTION=sync)') Yellow
    }
}

Write-Host ''
if ($Apply) {
    Write-Host 'Selesai. Jalankan berikutnya:  php artisan optimize:clear' -ForegroundColor Cyan
    Write-Host 'Lalu jalankan bersih_database.sql di phpMyAdmin.' -ForegroundColor Cyan
} else {
    Write-Host 'Ini hanya pratinjau. Tambahkan -Apply untuk benar-benar mengerjakan.' -ForegroundColor Cyan
}
