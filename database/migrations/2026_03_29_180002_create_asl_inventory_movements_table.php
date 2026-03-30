<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asl_inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_item_id');
            $table->string('type', 32);
            $table->decimal('quantity_delta', 20, 4);
            $table->decimal('quantity_after', 20, 4);
            $table->timestamp('occurred_at');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('inventory_import_batch_id')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('asl_inventory_items')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('asl_users')->nullOnDelete();
            $table->foreign('inventory_import_batch_id')->references('id')->on('asl_inventory_import_batches')->nullOnDelete();
            $table->index(['inventory_item_id', 'occurred_at']);
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asl_inventory_movements');
    }
};
