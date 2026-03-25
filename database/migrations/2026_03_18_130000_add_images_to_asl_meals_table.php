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
        Schema::table('asl_meals', function (Blueprint $table): void {
            $table->string('thumbnail_image')->nullable()->after('description');
            $table->json('images')->nullable()->after('thumbnail_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asl_meals', function (Blueprint $table): void {
            $table->dropColumn(['thumbnail_image', 'images']);
        });
    }
};
