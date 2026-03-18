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
        Schema::create('asl_delivery_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('asl_users')->cascadeOnDelete();
            $table->foreignUuid('zone_id')->nullable()->constrained('asl_delivery_zones')->nullOnDelete();
            $table->string('label', 50)->nullable();
            $table->string('address_line');
            $table->string('city');
            $table->string('zip_code', 20);
            $table->decimal('longitude', 11, 8);
            $table->decimal('latitude', 10, 8);
            $table->text('delivery_notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index('user_id');
            $table->index('zone_id');
            $table->index('zip_code');
            $table->index('status');
            $table->index(['user_id', 'is_default']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asl_delivery_addresses');
    }
};
