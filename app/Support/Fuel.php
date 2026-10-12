<?php

namespace App\Support;

/** Pemetaan jenis bahan bakar ke kelas warna (palet kompon ban balap). */
class Fuel
{
    public static function css(?string $fuel): string
    {
        $f = strtolower((string) $fuel);

        return match (true) {
            $f === '' => 'fuel-none',
            str_contains($f, 'electric') && ! str_contains($f, 'hybrid') => 'fuel-ev',
            str_contains($f, 'hybrid') => 'fuel-hybrid',
            str_contains($f, 'diesel') => 'fuel-diesel',
            str_contains($f, 'gasoline') => 'fuel-petrol',
            default => 'fuel-alt',
        };
    }

    /** Kelompok bahan bakar untuk statistik: Gasoline, Diesel, Hybrid, Electric, atau Other (null bila kosong). */
    public static function group(?string $fuel): ?string
    {
        $f = strtolower((string) $fuel);

        return match (true) {
            $f === '' => null,
            str_contains($f, 'hybrid') => 'Hybrid',
            str_contains($f, 'electric') => 'Electric',
            str_contains($f, 'diesel') => 'Diesel',
            str_contains($f, 'gasoline') => 'Gasoline',
            default => 'Other',
        };
    }
}
