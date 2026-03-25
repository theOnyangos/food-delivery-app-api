<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asl_meal_nutritions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('meal_id')->constrained('asl_meals')->cascadeOnDelete();
            $table->decimal('fats', 8, 2)->nullable();
            $table->decimal('protein', 8, 2)->nullable();
            $table->decimal('carbs', 8, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('meal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asl_meal_nutritions');
    }
};
