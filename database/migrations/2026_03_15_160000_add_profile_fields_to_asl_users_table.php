<?php

use App\Services\AuthService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asl_users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->string('account_number')->nullable()->unique()->after('email');
            $table->text('two_factor_secret')->nullable()->after('remember_token');
        });

        DB::table('asl_users')
            ->orderBy('id')
            ->select(['id', 'name'])
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $name = trim((string) $user->name);
                    $parts = preg_split('/\s+/', $name) ?: [];
                    $firstName = $parts[0] ?? 'User';
                    $lastName = count($parts) > 1 ? end($parts) : 'Account';
                    $middleName = null;

                    if (count($parts) > 2) {
                        $middleName = implode(' ', array_slice($parts, 1, -1));
                    }

                    DB::table('asl_users')
                        ->where('id', $user->id)
                        ->update([
                            'first_name' => $firstName,
                            'middle_name' => $middleName,
                            'last_name' => $lastName,
                            'account_number' => AuthService::generateAccountNumber(),
                        ]);
                }
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
