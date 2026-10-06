<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_models', function (Blueprint $table) {
            $table->string('image_url', 500)->nullable()->after('description');
            $table->string('image_credit', 500)->nullable()->after('image_url');
            // Diisi walau pencarian gagal, supaya model yang tidak punya foto tidak dicari berulang-ulang.
            $table->timestamp('image_checked_at')->nullable()->after('image_credit');
        });
    }

    public function down(): void
    {
        Schema::table('car_models', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'image_credit', 'image_checked_at']);
        });
    }
};
