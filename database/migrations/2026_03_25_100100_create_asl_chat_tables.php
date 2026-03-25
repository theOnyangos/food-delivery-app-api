<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asl_chat_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_user_id')->constrained('asl_users')->cascadeOnDelete();
            $table->string('status')->default('open');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::table('asl_chat_conversations', function (Blueprint $table) {
            $table->index(['vendor_user_id', 'status']);
        });

        Schema::create('asl_chat_conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained('asl_chat_conversations')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('asl_users')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
        });

        Schema::table('asl_chat_conversation_participants', function (Blueprint $table) {
            $table->unique(['conversation_id', 'user_id'], 'asl_chat_conv_part_unique');
            $table->index('user_id');
        });

        Schema::create('asl_chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('asl_chat_conversations')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('asl_users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->timestamps();
        });

        Schema::table('asl_chat_messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('asl_chat_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('message_id')->constrained('asl_chat_messages')->cascadeOnDelete();
            $table->foreignUuid('media_id')->constrained('asl_media')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('asl_chat_message_attachments', function (Blueprint $table) {
            $table->unique(['message_id', 'media_id']);
        });

        Schema::create('asl_chat_support_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_user_id')->constrained('asl_users')->cascadeOnDelete();
            $table->foreignUuid('vendor_user_id')->constrained('asl_users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('asl_chat_support_allocations', function (Blueprint $table) {
            $table->index('support_user_id');
            $table->index('vendor_user_id');
            $table->unique(['support_user_id', 'vendor_user_id'], 'asl_chat_alloc_support_vendor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asl_chat_support_allocations');
        Schema::dropIfExists('asl_chat_message_attachments');
        Schema::dropIfExists('asl_chat_messages');
        Schema::dropIfExists('asl_chat_conversation_participants');
        Schema::dropIfExists('asl_chat_conversations');
    }
};
