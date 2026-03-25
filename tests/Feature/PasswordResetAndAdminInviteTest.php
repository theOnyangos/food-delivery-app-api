<?php

use App\Models\Role;
use App\Models\User;
use App\Notifications\QueuedResetPasswordNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('sends a password reset notification whose mail action points at the SPA reset URL', function (): void {
    config(['app.client_url' => 'https://dashboard.example.com']);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Notification::fake();

    $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertSentTo($user, QueuedResetPasswordNotification::class);

    $sent = Notification::sent($user, QueuedResetPasswordNotification::class);
    expect($sent)->not->toBeEmpty();

    /** @var QueuedResetPasswordNotification $notification */
    $notification = $sent[0];
    $mail = $notification->toMail($user);

    $expectedPrefix = 'https://dashboard.example.com/reset-password?';
    expect($mail->actionUrl)->toStartWith($expectedPrefix);
    expect($mail->actionUrl)->toContain('token=');
    expect($mail->actionUrl)->toContain(rawurlencode($user->email));
});

it('sets email_verified_at and allows login after password reset for previously unverified users', function (): void {
    $user = User::factory()->unverified()->create([
        'password' => Hash::make('old-password'),
    ]);

    Notification::fake();
    $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();

    $sent = Notification::sent($user, QueuedResetPasswordNotification::class);
    $token = $sent[0]->token;

    $this->postJson('/api/auth/reset-password', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();

    $login = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'newpassword123',
    ]);

    $login->assertOk()->assertJsonPath('success', true);
});

it('creates an invited user and sends a reset notification', function (): void {
    config(['app.client_url' => 'https://dashboard.example.com']);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Admin');

    $customerRole = Role::query()->where('name', 'Customer')->firstOrFail();

    Notification::fake();

    $response = $this->postJson(
        '/api/admin/users',
        [
            'first_name' => 'Invited',
            'last_name' => 'User',
            'email' => 'invited-user@example.com',
            'role_ids' => [$customerRole->id],
        ],
        ['Authorization' => 'Bearer '.$admin->createToken('test')->plainTextToken]
    );

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $invited = User::query()->where('email', 'invited-user@example.com')->firstOrFail();
    expect($invited->email_verified_at)->toBeNull();

    Notification::assertSentTo($invited, QueuedResetPasswordNotification::class);
});

it('returns 403 when a user without manage users tries to invite', function (): void {
    $customer = User::factory()->create(['email_verified_at' => now()]);
    $customer->assignRole('Customer');

    $response = $this->postJson(
        '/api/admin/users',
        [
            'first_name' => 'X',
            'last_name' => 'Y',
            'email' => 'no-access@example.com',
            'role_names' => ['Customer'],
        ],
        ['Authorization' => 'Bearer '.$customer->createToken('test')->plainTextToken]
    );

    $response->assertForbidden();
});
