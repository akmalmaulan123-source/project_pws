<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sebagian data transmisi berupa deskripsi panjang (hingga ~150 karakter),
     * sedangkan kolom awal hanya 100 karakter, sehingga MySQL menolaknya ("Data too long").
     */
    public function up(): void
    {
        Schema::table('engines', function (Blueprint $table) {
            $table->string('transmission', 255)->nullable()->change();
            $table->string('drivetrain', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('engines', function (Blueprint $table) {
            $table->string('transmission', 100)->nullable()->change();
            $table->string('drivetrain', 100)->nullable()->change();
        });
    }
};
