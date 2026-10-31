<?php

namespace App\Support;

/**
 * Menyusun daftar "Top picks" dari data mesin. Murni PHP tanpa database, jadi mudah diuji.
 *
 * Tiap model diwakili oleh VARIAN AWAL generasi terbarunya: dari generasi terbaru, ambil mesin termurah
 * (kalau harganya sama, yang tenaganya paling kecil). Tenaga, 0-100, top speed, dan harga semuanya dari mesin
 * yang sama. Ini penting karena harga di katalog berlaku per model: kalau tenaga diambil dari varian
 * terkuat sepanjang masa tetapi harganya dari varian dasar, mobil bervarian banyak selalu tampak murah.
 */
final class TopPicks
{
    /** Batas harga daftar "tercepat" (USD). */
    public const BUDGET_CAP = 100000;

    /** Batas harga daftar hybrid & listrik (USD), supaya hypercar jutaan dolar tidak mendominasi. */
    public const ELECTRIFIED_CAP = 250000;

    /** Hanya model yang punya generasi dari tahun ini ke atas yang jadi "pilihan" (mobil lawas bukan untuk dibeli). */
    public const MIN_GEN_YEAR = 2010;

    /** Tenaga minimum (hp) agar masuk daftar value, supaya mobil murah bertenaga kecil tidak mendominasi. */
    public const VALUE_MIN_HP = 300;

    /** Jumlah mobil di tiap daftar. */
    public const LIST_SIZE = 5;

    /**
     * @param  array<int, array<string, mixed>>  $rows  satu baris per mesin: model_id, gen_year, fuel_type, power_hp, zero, top, price
     * @return array{value: array, quick: array, electrified: array}
     */
    public static function build(array $rows): array
    {
        $all = self::recent(self::summarize($rows));
        $electrified = self::recent(self::summarize(array_filter(
            $rows,
            fn (array $r) => in_array(Fuel::group($r['fuel_type'] ?? null), ['Hybrid', 'Electric'], true)
        )));

        return [
            'value' => self::top(
                array_filter($all, fn (array $m) => $m['value'] !== null && $m['hp'] >= self::VALUE_MIN_HP),
                fn (array $a, array $b) => $b['value'] <=> $a['value'],
                self::LIST_SIZE
            ),
            'quick' => self::top(
                array_filter($all, fn (array $m) => $m['price'] !== null && $m['price'] <= self::BUDGET_CAP && $m['zero'] !== null),
                fn (array $a, array $b) => $a['zero'] <=> $b['zero'],
                self::LIST_SIZE
            ),
            'electrified' => self::top(
                array_filter($electrified, fn (array $m) => $m['pi'] !== null && $m['price'] !== null && $m['price'] <= self::ELECTRIFIED_CAP),
                fn (array $a, array $b) => $b['pi'] <=> $a['pi'],
                self::LIST_SIZE
            ),
        ];
    }

    /** Semua id model yang muncul di daftar (tanpa duplikat), untuk dimuat dari database sekali jalan. */
    public static function ids(array $picks): array
    {
        $lists = [$picks['value'], $picks['quick'], $picks['electrified']];

        return array_values(array_unique(array_map(fn (array $m) => $m['id'], array_merge(...$lists))));
    }

    /** Satu ringkasan per model, dari varian awal generasi terbarunya. */
    private static function summarize(array $rows): array
    {
        $byModel = [];
        foreach ($rows as $r) {
            $byModel[(int) $r['model_id']][] = [
                'gen' => is_numeric($r['gen_year'] ?? null) ? (int) $r['gen_year'] : null,
                'hp' => self::num($r['power_hp'] ?? null),
                'zero' => self::num($r['zero'] ?? null),
                'top' => self::num($r['top'] ?? null),
                'price' => self::num($r['price'] ?? null),
            ];
        }

        $models = [];
        foreach ($byModel as $id => $engines) {
            $base = self::startingVariant($engines);

            $models[$id] = [
                'id' => $id,
                'gen' => $base['gen'],
                'hp' => $base['hp'],
                'zero' => $base['zero'],
                'top' => $base['top'],
                'price' => $base['price'],
                'value' => $base['hp'] !== null && $base['price'] !== null ? $base['hp'] / ($base['price'] / 1000) : null,
                'pi' => Rating::pi($base['hp'], $base['zero'], $base['top']),
            ];
        }

        return $models;
    }

    /** Buang model yang generasi terbarunya sebelum MIN_GEN_YEAR (tanpa data tahun tetap dipertahankan). */
    private static function recent(array $models): array
    {
        return array_filter($models, fn (array $m) => $m['gen'] === null || $m['gen'] >= self::MIN_GEN_YEAR);
    }

    /** Mesin termurah dari generasi terbaru (seri harga: tenaga terkecil). Tanpa data tahun, semua mesin dianggap satu generasi. */
    private static function startingVariant(array $engines): array
    {
        $newest = max(array_map(fn (array $e) => $e['gen'] ?? PHP_INT_MIN, $engines));
        $pool = array_values(array_filter($engines, fn (array $e) => ($e['gen'] ?? PHP_INT_MIN) === $newest));

        // Generasi terbaru tanpa data tenaga sama sekali: pakai semua mesin model itu.
        if (array_filter($pool, fn (array $e) => $e['hp'] !== null) === []) {
            $pool = $engines;
        }

        usort($pool, function (array $a, array $b) {
            // Mesin berharga lebih dulu, lalu yang termurah, lalu yang tenaganya terkecil (tenaga kosong paling akhir).
            return [$a['price'] === null, $a['price'], $a['hp'] === null, $a['hp']]
                <=> [$b['price'] === null, $b['price'], $b['hp'] === null, $b['hp']];
        });

        return $pool[0];
    }

    /** Urutkan dengan pembanding utama, lalu tenaga terbesar, lalu id (hasil selalu sama), dan ambil $n teratas. */
    private static function top(array $items, callable $primary, int $n): array
    {
        $items = array_values($items);
        usort($items, fn (array $a, array $b) => ($primary($a, $b) ?: (($b['hp'] ?? 0) <=> ($a['hp'] ?? 0))) ?: ($a['id'] <=> $b['id']));

        return array_slice($items, 0, $n);
    }

    private static function num(mixed $v): ?float
    {
        return is_numeric($v) && (float) $v > 0 ? (float) $v : null;
    }
}
