<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asl_ai_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('session_id', 255)->nullable()->index();
            $table->string('type', 20)->default('vendor')->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('asl_ai_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id')->index();
            $table->string('role', 20);
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('asl_ai_agent_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('setting_key', 255)->unique();
            $table->text('setting_value')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('asl_ai_daily_usage', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('usage_date')->index();
            $table->string('user_type', 20)->default('vendor')->index();
            $table->string('identity_type', 20)->index();
            $table->string('identity', 255)->index();
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamps();
            $table->unique(['usage_date', 'user_type', 'identity_type', 'identity'], 'asl_ai_daily_usage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asl_ai_daily_usage');
        Schema::dropIfExists('asl_ai_agent_settings');
        Schema::dropIfExists('asl_ai_messages');
        Schema::dropIfExists('asl_ai_conversations');
    }
};
