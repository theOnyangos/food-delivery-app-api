<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasTable('asl_users')) {
            Schema::rename('users', 'asl_users');
        }

        if (Schema::hasTable('password_reset_tokens') && ! Schema::hasTable('asl_password_reset_tokens')) {
            Schema::rename('password_reset_tokens', 'asl_password_reset_tokens');
        }

        if (Schema::hasTable('personal_access_tokens') && ! Schema::hasTable('asl_personal_access_tokens')) {
            Schema::rename('personal_access_tokens', 'asl_personal_access_tokens');
        }

        if (Schema::hasTable('sessions') && ! Schema::hasTable('asl_sessions')) {
            Schema::rename('sessions', 'asl_sessions');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('asl_sessions') && ! Schema::hasTable('sessions')) {
            Schema::rename('asl_sessions', 'sessions');
        }

        if (Schema::hasTable('asl_personal_access_tokens') && ! Schema::hasTable('personal_access_tokens')) {
            Schema::rename('asl_personal_access_tokens', 'personal_access_tokens');
        }

        if (Schema::hasTable('asl_password_reset_tokens') && ! Schema::hasTable('password_reset_tokens')) {
            Schema::rename('asl_password_reset_tokens', 'password_reset_tokens');
        }

        if (Schema::hasTable('asl_users') && ! Schema::hasTable('users')) {
            Schema::rename('asl_users', 'users');
        }
    }
};
