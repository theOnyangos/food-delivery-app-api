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
        Schema::create('asl_meal_ingredients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('meal_id')->constrained('asl_meals')->cascadeOnDelete();
            $table->string('meal_type', 100)->nullable();
            $table->json('metadata');
            $table->timestamps();

            $table->index('meal_id');
            $table->index('meal_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asl_meal_ingredients');
    }
};
