<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Engine;
use App\Models\Generation;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

#[AsCommand(name: 'cars:import-prices', description: 'Isi harga (USD) mesin dari file CSV: kolom make, model, price_usd (opsional: engine_label)')]
class ImportPrices extends Command
{
    protected $signature = 'cars:import-prices
        {file=database/data/prices.csv : Path file CSV (relatif terhadap root project)}
        {--overwrite : Timpa harga yang sudah terisi (default: hanya isi yang masih kosong)}
        {--dry-run : Hanya tampilkan hasil, tanpa menyimpan}';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! str_starts_with($path, '/') && ! preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = base_path($path);
        }
        if (! is_file($path) || ! ($fh = fopen($path, 'r'))) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $header = fgetcsv($fh);
        if (! $header) {
            $this->error('File CSV kosong.');

            return self::FAILURE;
        }
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header);
        foreach (['make', 'model', 'price_usd'] as $need) {
            if (! in_array($need, $header, true)) {
                $this->error("Kolom wajib tidak ada: {$need}. Header yang dibaca: ".implode(', ', $header));

                return self::FAILURE;
            }
        }

        $dry = (bool) $this->option('dry-run');
        $overwrite = (bool) $this->option('overwrite');
        $updated = 0;
        $skipped = 0;
        $missing = [];
        $line = 1;

        while (($raw = fgetcsv($fh)) !== false) {
            $line++;
            if (count($raw) === 1 && trim((string) $raw[0]) === '') {
                continue;
            }
            $row = [];
            foreach ($header as $i => $col) {
                $row[$col] = trim((string) ($raw[$i] ?? ''));
            }

            // "$32,500", "32.500", "32500" -> 32500
            $price = (int) preg_replace('/\D/', '', $row['price_usd']);
            if ($price <= 0) {
                $missing[] = "baris {$line}: harga tidak valid ({$row['price_usd']})";

                continue;
            }

            $brand = Brand::whereRaw('lower(name) = ?', [mb_strtolower($row['make'])])->first();
            $model = $brand
                ? CarModel::where('brand_id', $brand->id)->whereRaw('lower(name) = ?', [mb_strtolower($row['model'])])->first()
                : null;
            if (! $model) {
                $missing[] = "baris {$line}: {$row['make']} {$row['model']} tidak ditemukan di katalog";

                continue;
            }

            $query = Engine::query()->whereIn(
                'generation_id',
                Generation::where('car_model_id', $model->id)->select('id')
            );
            if (($row['engine_label'] ?? '') !== '') {
                $query->where('label', $row['engine_label']);
            }
            if (! $overwrite) {
                $query->whereNull('price_usd');
            }

            $count = $dry ? $query->count() : $query->update(['price_usd' => $price]);
            $count > 0 ? $updated += $count : $skipped++;
        }
        fclose($fh);

        foreach ($missing as $m) {
            $this->warn($m);
        }
        $prefix = $dry ? '[dry-run] ' : '';
        $this->info("{$prefix}Selesai: {$updated} mesin diberi harga, {$skipped} baris tidak mengubah apa pun, ".count($missing).' baris dilewati.');

        return self::SUCCESS;
    }
}
