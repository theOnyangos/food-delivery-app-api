<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asl_pos_sales', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('receipt_number', 64)->unique();
            $table->foreignUuid('daily_menu_id')->nullable()->constrained('asl_daily_menus')->nullOnDelete();
            $table->foreignUuid('sold_by')->constrained('asl_users')->restrictOnDelete();
            $table->string('order_type', 64);
            $table->string('customer_email')->nullable();
            $table->json('totals');
            $table->json('lines');
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['sold_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asl_pos_sales');
    }
};
