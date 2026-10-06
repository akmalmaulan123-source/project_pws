<?php

namespace App\Console\Commands;

use App\Models\CarModel;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LinkCarImages extends Command
{
    protected $signature = 'cars:link-images
                            {--list : Hanya buat daftar kelompok model yang belum punya foto (CSV), tanpa memasang apa pun}
                            {--replace : Foto kelompok/merek juga menggantikan foto lama dari Wikipedia (foto manual per model tetap aman)}
                            {--dir=images/source : Folder foto (relatif terhadap public/)}';

    protected $description = 'Pasang foto mobil buatan sendiri dari public/images/source ke car_models: per model, per kelompok varian, atau per merek';

    private const EXT = ['jpg', 'jpeg', 'png', 'webp'];

    private const C_MODEL = 'Foto manual';

    private const C_GROUP = 'Foto kelompok';

    private const C_BRAND = 'Foto merek';

    public function handle(): int
    {
        $models = CarModel::query()->with('brand')->orderBy('id')->get();

        if ($this->option('list')) {
            return $this->writeList($models);
        }

        $rel = trim((string) $this->option('dir'), '/\\');
        $dir = public_path($rel);

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
            $this->warn("Folder dibuat: public/{$rel}. Isi dengan foto lalu jalankan perintah ini lagi.");

            return self::SUCCESS;
        }

        /*
         * Nama file (tanpa ekstensi) dibaca sebagai awalan nama "merek model":
         *   123.jpg                  -> ID model, hanya model itu
         *   porsche-911-gt3-rs.jpg   -> model itu saja
         *   porsche-911.jpg          -> semua model berawalan "911" (911 Carrera, 911 Turbo, ...)
         *   porsche.jpg              -> semua model Porsche
         * Kalau beberapa file cocok, yang paling spesifik (paling panjang) menang.
         */
        $byId = $models->keyBy('id');
        $files = [];      // file => slug
        $idFiles = [];    // file => model
        $unmatched = [];

        foreach (scandir($dir) as $file) {
            if (! in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::EXT, true)) {
                continue;
            }
            $stem = pathinfo($file, PATHINFO_FILENAME);

            if (ctype_digit($stem)) {
                if ($byId->has((int) $stem)) {
                    $idFiles[$file] = $byId[(int) $stem];
                } else {
                    $unmatched[] = $file;
                }

                continue;
            }

            $files[$file] = Str::slug($stem);
        }

        $count = array_fill_keys(array_merge(array_keys($files), array_keys($idFiles)), 0);
        $replace = (bool) $this->option('replace');
        $auto = [self::C_GROUP, self::C_BRAND];

        foreach ($models as $m) {
            $slug = $this->slug($m);
            $brandSlug = Str::slug($m->brand->name);

            // File id menang mutlak
            $idFile = array_search($m, $idFiles, true);
            if ($idFile !== false) {
                $this->assign($m, $rel, $idFile, self::C_MODEL);
                $count[$idFile]++;

                continue;
            }

            // Cari file terpanjang yang menjadi awalan nama model (batas kata)
            $bestFile = null;
            $bestLen = 0;
            foreach ($files as $file => $fs) {
                if ($fs === '' || strlen($fs) <= $bestLen) {
                    continue;
                }
                if ($slug === $fs || str_starts_with($slug, $fs.'-')) {
                    $bestFile = $file;
                    $bestLen = strlen($fs);
                }
            }

            if ($bestFile === null) {
                continue;
            }

            $fs = $files[$bestFile];
            $credit = $slug === $fs ? self::C_MODEL : ($fs === $brandSlug ? self::C_BRAND : self::C_GROUP);

            // Foto spesifik satu model selalu dipasang; foto kelompok/merek tidak menimpa foto yang sudah ada,
            // kecuali foto itu juga hasil kelompok/merek (atau --replace untuk foto Wikipedia).
            $canReplace = $credit === self::C_MODEL
                || ! $m->image_url
                || in_array($m->image_credit, $auto, true)
                || ($replace && $m->image_credit !== self::C_MODEL);

            if (! $canReplace) {
                continue;
            }

            $this->assign($m, $rel, $bestFile, $credit);
            $count[$bestFile]++;
        }

        $total = 0;
        foreach ($count as $file => $n) {
            if ($n === 0) {
                $unmatched[] = $file;

                continue;
            }
            $total += $n;
            $this->line("  OK  {$file}  ->  {$n} model");
        }

        $names = $models->map(fn ($m) => $this->slug($m))->all();
        foreach (array_unique($unmatched) as $file) {
            $this->warn("  ??  {$file}  tidak memengaruhi model mana pun (nama tidak cocok, atau semua modelnya sudah punya foto)".$this->suggest($file, $names));
        }

        Cache::forget('home.feature.v2');
        $used = count(array_filter($count));
        $this->info("Selesai: {$total} model mendapat foto dari {$used} file.");

        $left = CarModel::whereNull('image_url')->count();
        $this->line($left ? "Masih {$left} model tanpa foto. Lihat daftarnya: php artisan cars:link-images --list" : 'Semua model sudah punya foto.');

        return self::SUCCESS;
    }

    private function assign(CarModel $m, string $rel, string $file, string $credit): void
    {
        $m->forceFill([
            'image_url' => '/'.$rel.'/'.rawurlencode($file),
            'image_credit' => $credit,
            'image_checked_at' => now(),
        ])->save();
    }

    private function slug(CarModel $m): string
    {
        return Str::slug($m->brand->name.' '.$m->name);
    }

    /** Kunci kelompok varian: merek + kata pertama model ("Macan GTS" -> porsche-macan). */
    private function groupKey(CarModel $m): string
    {
        $tokens = explode('-', Str::slug($m->name));
        $first = $tokens[0] ?? '';
        if (strlen($first) < 2 && isset($tokens[1])) {
            $first .= '-'.$tokens[1];
        }

        return trim(Str::slug($m->brand->name).'-'.$first, '-');
    }

    /** Saran nama file terdekat untuk file yang tidak cocok. */
    private function suggest(string $file, array $names): string
    {
        $stem = Str::slug(pathinfo($file, PATHINFO_FILENAME));
        $best = null;
        $bestPct = 0;
        foreach ($names as $name) {
            similar_text($stem, $name, $pct);
            if ($pct > $bestPct) {
                $bestPct = $pct;
                $best = $name;
            }
        }

        return $best && $bestPct >= 60 ? " (mungkin maksudnya: {$best})" : '';
    }

    private function writeList(Collection $models): int
    {
        $todo = $models->filter(fn ($m) => ! $m->image_url);
        $path = storage_path('app/car-photos-todo.csv');

        $groups = $todo->groupBy(fn ($m) => $this->groupKey($m));

        $fh = fopen($path, 'w');
        fputcsv($fh, ['nama_file_kelompok', 'jumlah_model', 'contoh_model'], ',', '"', '');
        foreach ($groups as $key => $items) {
            fputcsv($fh, [$key.'.jpg', $items->count(), $items->take(4)->pluck('name')->implode(' | ')], ',', '"', '');
        }
        fclose($fh);

        $this->info("{$todo->count()} dari {$models->count()} model belum punya foto, terbagi dalam {$groups->count()} kelompok varian.");
        $this->line("Daftar kelompok + nama file: {$path}");
        $this->newLine();
        $this->line('Satu foto bisa mewakili banyak model: pakai nama kelompok (porsche-macan.jpg), atau merek saja (porsche.jpg).');
        $this->line('Merek: '.$models->map(fn ($m) => Str::slug($m->brand->name))->unique()->map(fn ($b) => $b.'.jpg')->implode('  '));

        return self::SUCCESS;
    }
}
