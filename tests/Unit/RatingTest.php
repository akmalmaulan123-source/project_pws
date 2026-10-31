<?php

namespace Tests\Unit;

use App\Support\Rating;
use PHPUnit\Framework\TestCase;

class RatingTest extends TestCase
{
    public function test_pi_menggabungkan_tenaga_akselerasi_dan_top_speed(): void
    {
        // 911 GT3 RS: 518 hp, 3,2 s, 296 km/h
        $this->assertSame(625, Rating::pi(518, 3.2, 296));
        // Revuelto: 1.015 hp, 2,5 s, 350 km/h
        $this->assertSame(967, Rating::pi(1015, 2.5, 350));
    }

    public function test_pi_melewati_data_kosong_tanpa_mengarang_angka(): void
    {
        // Hanya tenaga: 500 / 1.050
        $this->assertSame(476, Rating::pi(500));
        // Tenaga + 0-100 saja: bobot dinormalkan (0,5 dan 0,25)
        $this->assertSame(484, Rating::pi(500, 3.7, null));
    }

    public function test_pi_tidak_melewati_batas_0_sampai_1000(): void
    {
        $this->assertSame(1000, Rating::pi(5000, 1.0, 999));
        $this->assertSame(0, Rating::pi(0.1, 99.0, 0.1));
    }

    public function test_pi_kosong_bila_tenaga_tidak_ada(): void
    {
        $this->assertNull(Rating::pi(null, 3.0, 300));
        $this->assertNull(Rating::pi(0, 3.0, 300));
    }
}
