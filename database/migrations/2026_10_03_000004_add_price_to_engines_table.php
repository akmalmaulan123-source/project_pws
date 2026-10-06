<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Harga opsional per varian mesin (USD, bilangan bulat). Diisi admin; dataset sumber tidak memuat harga. */
    public function up(): void
    {
        Schema::table('engines', function (Blueprint $table) {
            $table->unsignedInteger('price_usd')->nullable()->index()->after('curb_weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('engines', function (Blueprint $table) {
            $table->dropIndex(['price_usd']);
            $table->dropColumn('price_usd');
        });
    }
};
