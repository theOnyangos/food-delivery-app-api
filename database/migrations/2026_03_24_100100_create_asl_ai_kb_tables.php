<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asl_ai_kb_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 20)->index();
            $table->string('title', 255);
            $table->text('source_url')->nullable();
            $table->text('file_path')->nullable();
            $table->longText('content_raw')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamp('last_ingested_at')->nullable();
            $table->text('ingest_error')->nullable();
            $table->timestamps();
        });

        Schema::create('asl_ai_kb_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_id')->index();
            $table->unsignedInteger('chunk_index')->default(0);
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('source_id')->references('id')->on('asl_ai_kb_sources')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asl_ai_kb_chunks');
        Schema::dropIfExists('asl_ai_kb_sources');
    }
};
