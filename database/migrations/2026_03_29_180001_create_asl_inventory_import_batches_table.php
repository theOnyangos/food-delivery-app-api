<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asl_inventory_import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('file_name', 255)->nullable();
            $table->uuid('user_id');
            $table->string('kind', 32);
            $table->unsignedInteger('row_count')->default(0);
            $table->json('errors_json')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('asl_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asl_inventory_import_batches');
    }
};
