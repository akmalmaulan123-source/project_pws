<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->string('country', 80)->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('year_start')->nullable()->index();
            $table->unsignedSmallInteger('year_end')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'name']);
        });

        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained('car_models')->cascadeOnDelete();
            $table->string('name', 150);
            $table->unsignedSmallInteger('year_start')->nullable();
            $table->unsignedSmallInteger('year_end')->nullable();
            $table->string('body_type', 60)->nullable();
            $table->timestamps();

            $table->index('car_model_id');
        });

        Schema::create('engines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_id')->constrained()->cascadeOnDelete();
            $table->string('label', 200);
            $table->string('fuel_type', 60)->nullable()->index();
            $table->unsignedTinyInteger('cylinders')->nullable();
            $table->unsignedInteger('displacement_cc')->nullable();
            $table->decimal('power_hp', 7, 1)->nullable()->index();
            $table->decimal('torque_nm', 7, 1)->nullable();
            $table->string('transmission', 100)->nullable();
            $table->string('drivetrain', 100)->nullable();
            $table->decimal('zero_to_100_s', 5, 1)->nullable();
            $table->decimal('top_speed_kmh', 6, 1)->nullable();
            $table->decimal('fuel_economy_combined_l100', 5, 1)->nullable();
            $table->unsignedSmallInteger('length_mm')->nullable();
            $table->unsignedSmallInteger('width_mm')->nullable();
            $table->unsignedSmallInteger('height_mm')->nullable();
            $table->unsignedSmallInteger('wheelbase_mm')->nullable();
            $table->unsignedSmallInteger('curb_weight_kg')->nullable();
            $table->timestamps();

            $table->index('generation_id');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_model_id')->constrained('car_models')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'car_model_id']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_model_id')->constrained('car_models')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'car_model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('engines');
        Schema::dropIfExists('generations');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('brands');
    }
};
