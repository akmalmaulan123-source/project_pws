<?php

namespace Tests\Unit;

use App\Support\Rating;
use PHPUnit\Framework\TestCase;

class RatingTest extends TestCase
{
    public function test_kelas_mengikuti_batas_tenaga(): void
    {
        $this->assertSame('S', Rating::tier(700)[0]);
        $this->assertSame('A', Rating::tier(699.9)[0]);
        $this->assertSame('A', Rating::tier(500)[0]);
        $this->assertSame('B', Rating::tier(499.9)[0]);
        $this->assertSame('B', Rating::tier(300)[0]);
        $this->assertSame('C', Rating::tier(299.9)[0]);
        $this->assertSame('C', Rating::tier(80)[0]);
    }

    public function test_tanpa_data_tenaga_tidak_diberi_kelas(): void
    {
        $this->assertSame(Rating::NO_TIER, Rating::tier(null));
        $this->assertSame(Rating::NO_TIER, Rating::tier(0));
    }

    public function test_warna_kelas_dikembalikan_bersama_hurufnya(): void
    {
        $this->assertSame(['S', '#c98bff'], Rating::tier(1015));
        $this->assertSame(['A', '#ff5446'], Rating::tier(518));
    }

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
