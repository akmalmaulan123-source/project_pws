<?php

namespace Tests\Unit;

use App\Support\Fuel;
use PHPUnit\Framework\TestCase;

class FuelTest extends TestCase
{
    public function test_group_mengelompokkan_bahan_bakar(): void
    {
        $this->assertNull(Fuel::group(null));
        $this->assertNull(Fuel::group(''));
        $this->assertSame('Gasoline', Fuel::group('Gasoline'));
        $this->assertSame('Diesel', Fuel::group('Diesel'));
        $this->assertSame('Electric', Fuel::group('Electric'));
        $this->assertSame('Hybrid', Fuel::group('Mild Hybrid'));
        $this->assertSame('Other', Fuel::group('Hydrogen'));
    }

    public function test_hybrid_listrik_tidak_dihitung_sebagai_ev_murni(): void
    {
        $this->assertSame('fuel-hybrid', Fuel::css('Plug-in Hybrid Electric'));
        $this->assertSame('fuel-ev', Fuel::css('Electric'));
    }

    public function test_kelas_css_per_jenis(): void
    {
        $this->assertSame('fuel-none', Fuel::css(null));
        $this->assertSame('fuel-diesel', Fuel::css('Diesel'));
        $this->assertSame('fuel-petrol', Fuel::css('Gasoline'));
        $this->assertSame('fuel-alt', Fuel::css('Hydrogen'));
    }
}
