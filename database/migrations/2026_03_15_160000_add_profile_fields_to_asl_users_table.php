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
        Schema::table('asl_users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->string('account_number')->nullable()->unique()->after('email');
            $table->text('two_factor_secret')->nullable()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asl_users', function (Blueprint $table) {
            $table->dropUnique(['account_number']);
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'account_number', 'two_factor_secret']);
        });
    }
};
