<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Mengimpor dataset mobil (CSV di database/data) ke tabel brands, car_models, generations, engines.
 * Sumber data: https://github.com/gor3a/vehicle-makes-models (lisensi ODbL v1.0).
 */
class CarDataImporter
{
    private const CHUNK = 500;

    /**
     * Rentang nilai yang masuk akal untuk mobil penumpang. Dataset sumber berasal dari scraping dan
     * memuat sekitar <1% nilai rusak (mis. lebar 18263 mm). Nilai di luar rentang dikosongkan (NULL),
     * tidak ditebak. Format: kolom => [min, max].
     */
    private const RANGES = [
        'cylinders' => [1, 16],
        'displacement_cc' => [50, 9000],
        'power_hp' => [1, 3000],
        'torque_nm' => [1, 2500],
        'zero_to_100_s' => [1.5, 40],
        'top_speed_kmh' => [60, 500],
        'fuel_economy_combined_l100' => [0.5, 40],
        'length_mm' => [2000, 7500],
        'width_mm' => [1300, 2400],
        'height_mm' => [900, 2200],
        'wheelbase_mm' => [1500, 4500],
        'curb_weight_kg' => [400, 4500],
    ];

    /** Jumlah nilai yang dikosongkan per kolom pada impor terakhir. */
    private array $cleaned = [];

    public function __construct(private ?\Closure $log = null) {}

    public function isEmpty(): bool
    {
        return ! DB::table('brands')->exists();
    }

    public function wipe(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['engines', 'generations', 'reviews', 'favorites', 'car_models', 'brands'] as $t) {
            DB::table($t)->truncate();
        }
        Schema::enableForeignKeyConstraints();
    }

    /** @return array{brands:int, models:int, generations:int, engines:int, models_merged:int} */
    public function run(): array
    {
        if (! $this->isEmpty()) {
            throw new RuntimeException('Tabel brands sudah berisi data. Gunakan opsi --fresh untuk mengosongkan lalu impor ulang.');
        }

        $dir = database_path('data');
        $now = now()->toDateTimeString();
        $brandIds = $modelIds = $genIds = [];
        $counts = ['brands' => 0, 'models' => 0, 'generations' => 0, 'engines' => 0, 'models_merged' => 0];

        DB::transaction(function () use ($dir, $now, &$brandIds, &$modelIds, &$genIds, &$counts) {
            // 1. Brands
            $rows = [];
            foreach ($this->readCsv("$dir/brands.csv") as $r) {
                $id = ++$counts['brands'];
                $brandIds[$r['name']] = $id;
                $rows[] = [
                    'id' => $id, 'name' => $r['name'], 'slug' => Str::slug($r['name']),
                    'country' => $this->s($r['country']), 'created_at' => $now, 'updated_at' => $now,
                ];
            }
            $this->insertChunks('brands', $rows);
            $this->say("Brands: {$counts['brands']}");

            // 2. Models
            $rows = [];
            foreach ($this->readCsv("$dir/models.csv") as $r) {
                if (! isset($brandIds[$r['make']])) {
                    continue;
                }
                $modelKey = $r['make'].'|'.$this->key($r['model']);
                if (isset($modelIds[$modelKey])) {
                    $counts['models_merged'] = ($counts['models_merged'] ?? 0) + 1; // duplikat huruf besar/kecil: pakai yang pertama
                    continue;
                }
                $id = ++$counts['models'];
                $modelIds[$modelKey] = $id;
                $rows[] = [
                    'id' => $id, 'brand_id' => $brandIds[$r['make']], 'name' => $r['model'],
                    'year_start' => $this->i($r['year_start']), 'year_end' => $this->i($r['year_end']),
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
            $this->insertChunks('car_models', $rows);
            $this->say("Models: {$counts['models']}".($counts['models_merged'] ? " ({$counts['models_merged']} duplikat huruf besar/kecil digabung)" : ''));

            // 3 + 4. Generations & engines (satu baris CSV = satu mesin)
            $genRows = $engRows = [];
            foreach ($this->readCsv("$dir/engines.csv") as $r) {
                $modelKey = $r['make'].'|'.$this->key($r['model']);
                if (! isset($modelIds[$modelKey])) {
                    continue;
                }
                $genKey = $modelKey.'|'.$r['generation'].'|'.$r['gen_year_start'];
                if (! isset($genIds[$genKey])) {
                    $genIds[$genKey] = ++$counts['generations'];
                    $genRows[] = [
                        'id' => $genIds[$genKey], 'car_model_id' => $modelIds[$modelKey], 'name' => $r['generation'],
                        'year_start' => $this->i($r['gen_year_start']), 'year_end' => $this->i($r['gen_year_end']),
                        'body_type' => $this->s($r['body_type']), 'created_at' => $now, 'updated_at' => $now,
                    ];
                }
                $counts['engines']++;
                $engRows[] = [
                    'generation_id' => $genIds[$genKey], 'label' => $r['engine_label'], 'fuel_type' => $this->s($r['fuel_type']),
                    'cylinders' => $this->num('cylinders', $r['cylinders'], true),
                    'displacement_cc' => $this->num('displacement_cc', $r['displacement_cc'], true),
                    'power_hp' => $this->powerHp($r['power_hp'], $r['engine_label']),
                    'torque_nm' => $this->num('torque_nm', $r['torque_nm']),
                    'transmission' => $this->s($r['transmission']), 'drivetrain' => $this->s($r['drivetrain']),
                    'zero_to_100_s' => $this->num('zero_to_100_s', $r['zero_to_100_s']),
                    'top_speed_kmh' => $this->num('top_speed_kmh', $r['top_speed_kmh']),
                    'fuel_economy_combined_l100' => $this->num('fuel_economy_combined_l100', $r['fuel_economy_combined_l100']),
                    'length_mm' => $this->num('length_mm', $r['length_mm'], true),
                    'width_mm' => $this->num('width_mm', $r['width_mm'], true),
                    'height_mm' => $this->num('height_mm', $r['height_mm'], true),
                    'wheelbase_mm' => $this->num('wheelbase_mm', $r['wheelbase_mm'], true),
                    'curb_weight_kg' => $this->num('curb_weight_kg', $r['curb_weight_kg'], true),
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
            $this->insertChunks('generations', $genRows);
            $this->say("Generations: {$counts['generations']}");
            $this->insertChunks('engines', $engRows);
            $this->say("Engines: {$counts['engines']}");
        });

        return $counts;
    }

    private function insertChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    private function readCsv(string $path): \Generator
    {
        if (! is_file($path)) {
            throw new RuntimeException("File data tidak ditemukan: $path");
        }
        $fh = fopen($path, 'r');
        $header = fgetcsv($fh, 0, ',', '"', '');
        while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            if ($row === [null]) {
                continue;
            }
            yield array_combine($header, array_pad($row, count($header), ''));
        }
        fclose($fh);
    }

    /**
     * Kunci pembanding nama model: tanpa spasi tepi, tanpa aksen, huruf kecil.
     * MySQL (utf8mb4_unicode_ci) menganggap "C-Class" dan "C-CLASS" sama, jadi pembandingan
     * di sini harus sama longgarnya agar unique (brand_id, name) tidak bentrok.
     */
    private function key(string $v): string
    {
        return mb_strtolower(Str::ascii(trim($v)));
    }

    private function s(?string $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    private function i(?string $v): ?int
    {
        $v = trim((string) $v);

        return $v === '' || ! is_numeric($v) ? null : (int) round((float) $v);
    }

    private function f(?string $v): ?float
    {
        $v = trim((string) $v);

        return $v === '' || ! is_numeric($v) ? null : round((float) $v, 1);
    }

    /** Angka dari CSV; dikosongkan bila di luar rentang wajar (lihat RANGES). */
    private function num(string $col, ?string $v, bool $int = false): int|float|null
    {
        $n = $int ? $this->i($v) : $this->f($v);
        if ($n === null) {
            return null;
        }
        [$min, $max] = self::RANGES[$col];
        if ($n < $min || $n > $max) {
            $this->cleaned[$col] = ($this->cleaned[$col] ?? 0) + 1;

            return null;
        }

        return $n;
    }

    /**
     * Tenaga (hp). Angka "(150 HP)" pada label mesin dipakai sebagai pembanding:
     * - bila nilai kolom rusak/di luar rentang, pakai angka label;
     * - bila nilai kolom berbeda lebih dari 3x lipat dari label (mis. 2400 vs 240), pakai angka label.
     * Selisih kecil (mis. 260 vs 321) dibiarkan karena bisa berarti tenaga mesin vs tenaga sistem.
     */
    private function powerHp(?string $v, string $label): ?float
    {
        $fromLabel = null;
        if (preg_match('/\((\d+(?:\.\d+)?)\s*HP\)/i', $label, $m)) {
            $fromLabel = $this->f($m[1]);
            if ($fromLabel !== null && ($fromLabel < self::RANGES['power_hp'][0] || $fromLabel > self::RANGES['power_hp'][1])) {
                $fromLabel = null;
            }
        }

        $before = $this->cleaned['power_hp'] ?? 0;
        $n = $this->num('power_hp', $v);

        if ($n === null) {
            if ($fromLabel !== null) {
                $this->cleaned['power_hp'] = $before; // dipulihkan dari label, tidak dihitung hilang
            }

            return $fromLabel;
        }

        if ($fromLabel !== null && ($n > 3 * $fromLabel || $n < $fromLabel / 3)) {
            return $fromLabel;
        }

        return $n;
    }

    /** @return array<string,int> kolom => jumlah nilai rusak yang dikosongkan */
    public function cleanedCounts(): array
    {
        return $this->cleaned;
    }

    private function say(string $m): void
    {
        if ($this->log) {
            ($this->log)($m);
        }
    }
}
