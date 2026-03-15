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
        Schema::create('asl_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('model_type');
            $table->uuid('model_id');
            $table->string('collection_name')->default('default');
            $table->string('file_name');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->json('custom_properties')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asl_media');
    }
};
