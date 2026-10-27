<?php

namespace App\Support;

/**
 * Satu-satunya tempat rumus penilaian mobil: kelas performa (S/A/B/C) dan Performance Index (PI).
 * Dipakai kartu showroom, dan nanti Car Finder serta halaman bandingkan, supaya angkanya selalu sama.
 */
final class Rating
{
    /** Batas tenaga (hp) tiap kelas, dari yang tertinggi: [huruf, hp minimum, warna]. */
    public const TIERS = [
        ['S', 700, '#c98bff'],
        ['A', 500, '#ff5446'],
        ['B', 300, '#ffd21f'],
        ['C', 0, '#3fcf7b'],
    ];

    public const NO_TIER = ['–', '#9c9ca0'];

    /** Nilai acuan: tenaga 1.050 hp, 0-100 km/h dari 5,0 s ke 2,4 s, top speed 360 km/h = skor penuh. */
    private const HP_MAX = 1050;

    private const ZERO_SLOW = 5.0;

    private const ZERO_FAST = 2.4;

    private const TOP_MAX = 360;

    /** Bobot PI: tenaga 50%, akselerasi 25%, top speed 25%. */
    private const W_HP = 0.50;

    private const W_ZERO = 0.25;

    private const W_TOP = 0.25;

    /** Kelas performa dari tenaga maksimum: [huruf, warna]. */
    public static function tier(?float $hp): array
    {
        if ($hp === null || $hp <= 0) {
            return self::NO_TIER;
        }

        foreach (self::TIERS as [$letter, $min, $color]) {
            if ($hp >= $min) {
                return [$letter, $color];
            }
        }

        return self::NO_TIER;
    }

    /**
     * Performance Index 0-1000. Komponen yang datanya kosong dilewati dan bobot sisanya dinormalkan,
     * jadi mobil tanpa data 0-100 tetap bisa dinilai (tanpa mengarang angka). Tanpa data tenaga: null.
     */
    public static function pi(?float $hp, ?float $zero = null, ?float $top = null): ?int
    {
        if ($hp === null || $hp <= 0) {
            return null;
        }

        $parts = [[self::W_HP, self::unit($hp / self::HP_MAX)]];

        if ($zero !== null && $zero > 0) {
            $parts[] = [self::W_ZERO, self::unit((self::ZERO_SLOW - $zero) / (self::ZERO_SLOW - self::ZERO_FAST))];
        }

        if ($top !== null && $top > 0) {
            $parts[] = [self::W_TOP, self::unit($top / self::TOP_MAX)];
        }

        $weights = array_sum(array_column($parts, 0));
        $score = array_sum(array_map(fn ($p) => $p[0] * $p[1], $parts)) / $weights;

        return (int) round($score * 1000);
    }

    private static function unit(float $v): float
    {
        return max(0.0, min(1.0, $v));
    }
}
