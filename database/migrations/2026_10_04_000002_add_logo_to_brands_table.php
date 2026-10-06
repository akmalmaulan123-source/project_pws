<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('logo_url', 500)->nullable()->after('description');
            // Diisi walau logo tidak ketemu, supaya merek itu tidak dicari berulang-ulang.
            $table->timestamp('logo_checked_at')->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['logo_url', 'logo_checked_at']);
        });
    }
};
