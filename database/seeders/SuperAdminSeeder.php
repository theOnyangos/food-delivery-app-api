<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@asl.local');
        $password = env('SUPER_ADMIN_PASSWORD', 'SuperAdmin123!');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'first_name' => 'Super',
                'middle_name' => null,
                'last_name' => 'Admin',
                'password' => Hash::make($password),
                'account_number' => AuthService::generateAccountNumber(),
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole('Super Admin');
    }
}
