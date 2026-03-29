<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asl_pos_sales', function (Blueprint $table): void {
            $table->timestamp('receipt_email_sent_at')->nullable()->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::table('asl_pos_sales', function (Blueprint $table): void {
            $table->dropColumn('receipt_email_sent_at');
        });
    }
};
