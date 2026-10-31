<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Halaman yang tidak butuh data katalog, jadi aman dites tanpa mengisi database. */
class PageTest extends TestCase
{
    public function test_halaman_about_tampil(): void
    {
        $this->get('/about')->assertOk();
    }

    public function test_alamat_compare_sementara_diarahkan_ke_daftar_mobil(): void
    {
        $this->get('/compare')->assertRedirect(route('cars.index'));
    }

    public function test_alamat_tidak_dikenal_menampilkan_halaman_404_bertema(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Off the racing line');
    }

    public function test_respons_membawa_header_keamanan(): void
    {
        $this->get('/about')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_halaman_memuat_tag_pratinjau_tautan(): void
    {
        $this->get('/about')
            ->assertSee('property="og:title"', false)
            ->assertSee('rel="canonical"', false);
    }

    public function test_halaman_about_memuat_atribusi_data_dan_panduan_angka(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('How to read the numbers')
            ->assertSee('autoevolution.com')
            ->assertSee('Open Database License');
    }
}
