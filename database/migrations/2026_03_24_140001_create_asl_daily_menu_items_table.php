<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asl_daily_menu_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('daily_menu_id')->constrained('asl_daily_menus')->cascadeOnDelete();
            $table->foreignUuid('meal_id')->constrained('asl_meals')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('servings_available');
            $table->unsignedInteger('max_per_order')->nullable();
            $table->timestamps();

            $table->index(['daily_menu_id', 'sort_order']);
            $table->index('meal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asl_daily_menu_items');
    }
};
