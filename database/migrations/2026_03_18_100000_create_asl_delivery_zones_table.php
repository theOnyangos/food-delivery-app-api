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
        Schema::create('asl_delivery_zones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('zip_code', 20);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('minimum_order_amount')->nullable();
            $table->unsignedSmallInteger('estimated_delivery_minutes')->nullable();
            $table->boolean('is_serviceable')->default(true);
            $table->timestamps();

            $table->index('zip_code');
            $table->index('status');
            $table->index(['zip_code', 'status']);
            $table->index('is_serviceable');
            $table->unique(['name', 'zip_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asl_delivery_zones');
    }
};
