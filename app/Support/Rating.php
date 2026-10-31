<?php

namespace App\Support;

/**
 * Satu-satunya tempat rumus penilaian mobil: Performance Index (PI).
 * Dipakai kartu showroom, dan nanti Car Finder serta halaman bandingkan, supaya angkanya selalu sama.
 */
final class Rating
{
    /** Nilai acuan: tenaga 1.050 hp, 0-100 km/h dari 5,0 s ke 2,4 s, top speed 360 km/h = skor penuh. */
    private const HP_MAX = 1050;

    private const ZERO_SLOW = 5.0;

    private const ZERO_FAST = 2.4;

    private const TOP_MAX = 360;

    /** Bobot PI: tenaga 50%, akselerasi 25%, top speed 25%. */
    private const W_HP = 0.50;

    private const W_ZERO = 0.25;

    private const W_TOP = 0.25;

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
