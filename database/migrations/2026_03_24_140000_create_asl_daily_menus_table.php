<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asl_daily_menus', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->date('menu_date');
            $table->string('status', 20)->default('draft');
            $table->foreignUuid('created_by')->constrained('asl_users')->cascadeOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('menu_date');
            $table->index('status');
            $table->index(['status', 'menu_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asl_daily_menus');
    }
};
