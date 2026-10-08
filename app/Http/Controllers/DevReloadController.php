<?php

namespace App\Http\Controllers;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Hanya untuk development (route-nya cuma terdaftar saat APP_ENV=local).
 * Mengembalikan "sidik jari" file proyek: waktu ubah terbaru + jumlah file.
 * devreload.js membandingkannya tiap sepersekian detik; kalau berubah, halaman memuat ulang sendiri
 * (khusus perubahan CSS: stylesheet diganti tanpa memuat ulang halaman).
 */
class DevReloadController extends Controller
{
    public function __invoke()
    {
        clearstatcache();

        return response()->json([
            // Tampilan: file CSS saja, dipertukarkan tanpa reload
            'css' => self::stamp([public_path('css')], ['css']),
            // Backend + frontend lain: controller, model, route, config, view Blade, JS
            'code' => self::stamp([
                app_path(),
                base_path('routes'),
                config_path(),
                resource_path('views'),
                public_path('js'),
            ], ['php', 'js']),
        ])->header('Cache-Control', 'no-store');
    }

    /** @param  list<string>  $dirs  @param  list<string>  $extensions */
    public static function stamp(array $dirs, array $extensions): string
    {
        $newest = 0;
        $count = 0;

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), $extensions, true)) {
                    continue;
                }
                $newest = max($newest, $file->getMTime());
                $count++;
            }
        }

        return $newest.'-'.$count;
    }
}
