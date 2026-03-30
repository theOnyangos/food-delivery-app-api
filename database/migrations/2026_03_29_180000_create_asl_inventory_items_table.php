<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asl_inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku', 64)->nullable()->unique();
            $table->string('name');
            $table->string('image_url', 2048)->nullable();
            $table->decimal('quantity', 20, 4)->default(0);
            $table->string('unit', 16);
            $table->string('storage_location', 255)->nullable();
            $table->decimal('storage_temperature_celsius', 8, 2)->nullable();
            $table->date('expiration_date')->nullable();
            $table->decimal('low_stock_threshold', 20, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asl_inventory_items');
    }
};
