<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('returns otp challenge and then logs in successfully after otp verification', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'password' => Hash::make('password123'),
        'email_verified_at' => now(),
        'two_factor_secret' => Hash::make('123456'),
    ]);
    $user->assignRole('Customer');

    $loginResponse = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $loginResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'OTP verification required.')
        ->assertJsonPath('data.requires_two_factor', true)
        ->assertJsonPath('data.two_factor_enabled', true)
        ->assertJsonPath('data.user.id', $user->id);

    expect($loginResponse->json('data.otp_challenge_token'))->toBeString();
    expect($loginResponse->json('data.roles'))->toContain('Customer');

    $verifyResponse = $this->postJson('/api/auth/verify-login-otp', [
        'otp_challenge_token' => $loginResponse->json('data.otp_challenge_token'),
        'otp' => '123456',
    ]);

    $verifyResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Login successful.')
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.two_factor_enabled', true)
        ->assertJsonPath('data.token_type', 'Bearer');

    expect($verifyResponse->json('data.token'))->toBeString();
    expect($verifyResponse->json('data.roles'))->toContain('Customer');
});

it('returns validation error for invalid otp code during verification', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
        'email_verified_at' => now(),
        'two_factor_secret' => Hash::make('123456'),
    ]);

    $loginResponse = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $challengeToken = $loginResponse->json('data.otp_challenge_token');

    $verifyResponse = $this->postJson('/api/auth/verify-login-otp', [
        'otp_challenge_token' => $challengeToken,
        'otp' => '000000',
    ]);

    $verifyResponse->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Invalid OTP code.')
        ->assertJsonPath('data.reason', 'invalid_otp');
});
