<?php

namespace App\Console\Commands;

use App\Http\Controllers\HomeController;
use App\Models\CarModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class CutoutHeroCar extends Command
{
    protected $signature = 'cars:cutout
                            {id? : ID model; kosong = mobil yang tampil di hero}
                            {--all : Proses semua model yang punya foto}
                            {--force : Dengan --all: timpa PNG yang sudah ada}
                            {--python=python : Perintah Python (mis. python3 atau py)}';

    protected $description = 'Buat PNG transparan (tanpa background) dari foto mobil untuk hero';

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->cutoutAll();
        }

        $model = $this->argument('id')
            ? CarModel::with('brand')->find($this->argument('id'))
            : $this->heroModel();

        if (! $model || ! $model->image_url) {
            $this->error('Model tidak ditemukan atau belum punya foto. Jalankan: php artisan cars:fetch-images atau cars:link-images');

            return self::FAILURE;
        }

        $this->info("Memotong background: {$model->full_name}");
        $this->line('Run pertama mengunduh model AI (~180 MB) kalau belum ada di ~/.u2net, harap tunggu...');

        if (! $this->cutout($model, true)) {
            $this->error('Gagal. Pastikan: pip install rembg onnxruntime pillow numpy scipy');

            return self::FAILURE;
        }

        Cache::forget('home.feature.v2');
        $this->info("Selesai: public/images/cars/{$model->id}.png");

        return self::SUCCESS;
    }

    /** Potong background semua model yang punya foto; yang sudah ada PNG-nya dilewati kecuali --force. */
    private function cutoutAll(): int
    {
        $models = CarModel::with('brand')->whereNotNull('image_url')->orderBy('id')->get();

        if ($models->isEmpty()) {
            $this->warn('Belum ada model yang punya foto. Jalankan cars:fetch-images atau cars:link-images dulu.');

            return self::SUCCESS;
        }

        $done = $reused = $skipped = 0;
        $failed = [];
        $bySource = []; // foto sumber => PNG hasilnya (foto yang sama, mis. foto merek, hanya dipotong sekali)

        foreach ($models as $i => $model) {
            $n = $i + 1;
            $dest = public_path("images/cars/{$model->id}.png");
            $src = $this->source($model);

            if (is_file($dest) && ! $this->option('force')) {
                $skipped++;

                continue;
            }

            if (isset($bySource[$src])) {
                @mkdir(dirname($dest), 0775, true);
                copy($bySource[$src], $dest);
                $reused++;

                continue;
            }

            $this->line("[{$n}/{$models->count()}] {$model->full_name}");

            if ($this->cutout($model, false)) {
                $done++;
                $bySource[$src] = $dest;
            } else {
                $failed[] = $model->full_name;
                $this->warn('   gagal, dilewati');
            }
        }

        Cache::forget('home.feature.v2');
        $this->info("Selesai: {$done} dipotong, {$reused} disalin dari foto yang sama, {$skipped} dilewati (sudah ada), ".count($failed).' gagal.');
        foreach ($failed as $name) {
            $this->line("  - {$name}");
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function cutout(CarModel $model, bool $verbose): bool
    {
        $dir = public_path('images/cars');
        @mkdir($dir, 0775, true);
        $dest = "{$dir}/{$model->id}.png";

        $proc = new Process([
            $this->option('python'),
            base_path('tools/cutout.py'),
            $this->source($model),
            $dest,
        ]);
        $proc->setTimeout(900);
        $proc->run($verbose ? fn ($type, $buffer) => $this->output->write($buffer) : null);

        return $proc->isSuccessful();
    }

    /** Foto lokal (mis. /images/source/12.jpg) dibaca dari disk; foto dari internet dipakai apa adanya (URL). */
    private function source(CarModel $model): string
    {
        $url = (string) $model->image_url;

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            $file = public_path(rawurldecode(ltrim($url, '/')));

            if (is_file($file)) {
                return $file;
            }
        }

        return $url;
    }

    private function heroModel(): ?CarModel
    {
        $models = CarModel::with('brand')
            ->whereHas('brand', fn ($q) => $q->where('name', 'Porsche'))
            ->whereNotNull('image_url')
            ->get();

        foreach (HomeController::FEATURE_MODELS as $name) {
            if ($m = $models->firstWhere('name', $name)) {
                return $m;
            }
        }

        return $models->first();
    }
}
