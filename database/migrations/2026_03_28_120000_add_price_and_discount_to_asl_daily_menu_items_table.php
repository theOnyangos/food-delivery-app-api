<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asl_daily_menu_items', function (Blueprint $table): void {
            $table->decimal('price', 10, 2)->nullable()->after('max_per_order');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('asl_daily_menu_items', function (Blueprint $table): void {
            $table->dropColumn(['price', 'discount_percent']);
        });
    }
};
