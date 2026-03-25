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
        Schema::create('asl_meals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('asl_users')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('asl_meal_categories')->nullOnDelete();
            $table->string('title');
            $table->string('excerpt')->nullable();
            $table->text('description');
            $table->unsignedSmallInteger('cooking_time')->nullable();
            $table->unsignedSmallInteger('servings')->nullable();
            $table->unsignedSmallInteger('calories')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('tags')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('published_at');
            $table->index(['status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asl_meals');
    }
};
