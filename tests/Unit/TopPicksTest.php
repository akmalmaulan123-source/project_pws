<?php

namespace Tests\Unit;

use App\Support\TopPicks;
use PHPUnit\Framework\TestCase;

class TopPicksTest extends TestCase
{
    /** Satu baris mesin, bentuknya sama seperti hasil query di PicksController (angka bisa berupa string dari database). */
    private function row(int $model, ?int $gen, mixed $hp, mixed $zero, mixed $top, mixed $price, string $fuel = 'Gasoline'): array
    {
        return ['model_id' => $model, 'gen_year' => $gen, 'fuel_type' => $fuel, 'power_hp' => $hp, 'zero' => $zero, 'top' => $top, 'price' => $price];
    }

    private function rows(): array
    {
        return [
            $this->row(1, 2020, '800.0', '2.8', '330.0', 200000),       // value 4,0
            $this->row(2, 2020, 750, 3.0, 320, 100000),                 // value 7,5
            $this->row(3, 2020, 520, 3.5, 290, 60000),                  // value 8,67
            $this->row(4, 2020, 250, 6.0, 200, 25000),                  // di bawah 300 hp: tidak masuk value
            $this->row(5, 2020, 400, 4.0, 280, 90000, 'Plug-in Hybrid'),// hybrid
            $this->row(6, 2020, 300, 5.0, null, null, 'Electric'),      // listrik tanpa harga
            $this->row(7, 2020, 600, 3.9, 300, null),                   // tanpa harga
            $this->row(8, 2020, 300, null, null, 30000),                // model 8: varian awal 300 hp
            $this->row(8, 2020, 450, null, null, 30000),                // model 8: varian lebih kuat, harga sama
        ];
    }

    public function test_value_diurutkan_dan_dibatasi_tenaga_minimum(): void
    {
        $picks = TopPicks::build($this->rows());

        // Model 4 (250 hp) dan model 6/7 (tanpa harga) tidak ikut; sisanya urut dari value tertinggi.
        $this->assertSame([8, 3, 2, 5, 1], array_column($picks['value'], 'id'));
    }

    public function test_varian_kuat_tidak_meminjam_harga_varian_dasar(): void
    {
        $model8 = TopPicks::build($this->rows())['value'][0];

        // Harga $30 ribu sama untuk 300 hp dan 450 hp: yang dinilai varian awal (300 hp), bukan 450 hp.
        $this->assertSame(8, $model8['id']);
        $this->assertSame(300.0, $model8['hp']);
        $this->assertEqualsWithDelta(10.0, $model8['value'], 0.001);   // 300 hp / $30 ribu
    }

    public function test_hanya_generasi_terbaru_yang_dinilai(): void
    {
        $rows = [
            $this->row(1, 1990, 100, 11.0, 180, 50000),   // generasi lama: dilewati
            $this->row(1, 2022, 400, 4.5, 270, 50000),    // generasi terbaru
        ];

        $m = TopPicks::build($rows)['value'][0];
        $this->assertSame(400.0, $m['hp']);
        $this->assertEqualsWithDelta(8.0, $m['value'], 0.001);
    }

    public function test_varian_termurah_dipilih_lalu_tenaga_terkecil_bila_harga_sama(): void
    {
        $rows = [
            $this->row(1, 2022, 500, 3.5, 300, 80000),
            $this->row(1, 2022, 350, 4.5, 260, 60000),   // termurah
            $this->row(1, 2022, 320, 4.8, 250, 60000),   // harga sama, tenaga lebih kecil: menang
        ];

        $m = TopPicks::build($rows)['value'][0];
        $this->assertSame(320.0, $m['hp']);
        $this->assertSame(60000.0, $m['price']);
        $this->assertSame(4.8, $m['zero']);   // semua angka dari mesin yang sama
    }

    public function test_tanpa_data_tahun_semua_mesin_dianggap_satu_generasi(): void
    {
        $rows = [$this->row(1, null, 400, 4.0, 270, 50000), $this->row(1, null, 300, 5.0, 240, 50000)];

        $this->assertSame(300.0, TopPicks::build($rows)['value'][0]['hp']);
    }

    public function test_model_yang_generasi_terbarunya_lawas_tidak_masuk_daftar(): void
    {
        $rows = [
            $this->row(1, 1995, 300, 6.0, 240, 20000),   // terbaru 1995: terlalu lawas
            $this->row(2, 2012, 300, 6.0, 240, 20000),
        ];

        $picks = TopPicks::build($rows);
        $this->assertSame([2], array_column($picks['value'], 'id'));
    }

    public function test_tercepat_dibatasi_harga_dan_butuh_data_0_100(): void
    {
        $picks = TopPicks::build($this->rows());

        // Model 1 ($200 ribu) terlalu mahal; model 8 tidak punya data 0-100; model 6 dan 7 tidak berharga.
        $this->assertSame([2, 3, 5, 4], array_column($picks['quick'], 'id'));
    }

    public function test_hybrid_dan_listrik_dibatasi_harga_dan_dinilai_dari_varian_elektrifikasinya(): void
    {
        $picks = TopPicks::build($this->rows());

        // Model 6 tidak berharga, jadi tidak bisa dibandingkan dengan batas harga.
        $this->assertSame([5], array_column($picks['electrified'], 'id'));
        $this->assertSame(481, $picks['electrified'][0]['pi']);
    }

    public function test_hypercar_di_atas_batas_harga_tidak_masuk_daftar_hybrid(): void
    {
        $rows = [$this->row(1, 2022, 1200, 2.0, 400, 2000000, 'Electric'), $this->row(2, 2022, 400, 4.0, 250, 90000, 'Electric')];

        $this->assertSame([2], array_column(TopPicks::build($rows)['electrified'], 'id'));
    }

    public function test_model_tanpa_harga_tidak_masuk_value(): void
    {
        $picks = TopPicks::build([$this->row(1, 2022, 800, 2.8, 330, null)]);

        $this->assertSame([], $picks['value']);
        $this->assertSame([], $picks['quick']);
    }

    public function test_tanpa_data_sama_sekali_tidak_error(): void
    {
        $picks = TopPicks::build([]);

        $this->assertSame([], $picks['quick']);
        $this->assertSame([], $picks['electrified']);
        $this->assertSame([], TopPicks::ids($picks));
    }

    public function test_ids_mengumpulkan_semua_model_tanpa_duplikat(): void
    {
        $ids = TopPicks::ids(TopPicks::build($this->rows()));

        $this->assertSame(count($ids), count(array_unique($ids)));
        $this->assertNotContains(7, $ids);   // tanpa harga dan bukan hybrid
    }

    public function test_hasil_selalu_sama_untuk_nilai_seri(): void
    {
        $rows = [$this->row(9, 2022, 500, 3.0, 300, 50000), $this->row(7, 2022, 500, 3.0, 300, 50000)];

        $this->assertSame([7, 9], array_column(TopPicks::build($rows)['value'], 'id'));
    }
}
