<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asl_review_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::table('asl_review_categories', function (Blueprint $table) {
            $table->index('slug');
        });

        Schema::create('asl_review_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::table('asl_review_topics', function (Blueprint $table) {
            $table->index('slug');
        });

        Schema::create('asl_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('asl_users')->cascadeOnDelete();
            $table->string('reviewable_type');
            $table->uuid('reviewable_id');
            $table->unsignedTinyInteger('rating');
            $table->text('message');
            $table->string('status')->default('approved');
            $table->timestamps();
        });
        Schema::table('asl_reviews', function (Blueprint $table) {
            $table->index(['reviewable_type', 'reviewable_id']);
            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('asl_review_review_category', function (Blueprint $table) {
            $table->foreignUuid('review_id')->constrained('asl_reviews')->cascadeOnDelete();
            $table->foreignUuid('review_category_id')->constrained('asl_review_categories')->cascadeOnDelete();
            $table->primary(['review_id', 'review_category_id']);
        });

        Schema::create('asl_review_review_topic', function (Blueprint $table) {
            $table->foreignUuid('review_id')->constrained('asl_reviews')->cascadeOnDelete();
            $table->foreignUuid('review_topic_id')->constrained('asl_review_topics')->cascadeOnDelete();
            $table->primary(['review_id', 'review_topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asl_review_review_topic');
        Schema::dropIfExists('asl_review_review_category');
        Schema::dropIfExists('asl_reviews');
        Schema::dropIfExists('asl_review_topics');
        Schema::dropIfExists('asl_review_categories');
    }
};
