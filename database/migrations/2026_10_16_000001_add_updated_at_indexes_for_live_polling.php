<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LiveController menghitung MAX(updated_at) keempat tabel katalog tiap beberapa detik.
 * Tanpa index, MySQL memindai seluruh tabel (engines paling besar) pada setiap hitungan.
 */
return new class extends Migration
{
    private const TABLES = ['brands', 'car_models', 'generations', 'engines'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->index('updated_at');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['updated_at']);
            });
        }
    }
};
