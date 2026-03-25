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
        Schema::create('asl_meal_recipes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('meal_id')->constrained('asl_meals')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_pro_only')->default(false);
            $table->timestamps();

            $table->index('meal_id');
            $table->index('status');
            $table->index('is_pro_only');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asl_meal_recipes');
    }
};
