<?php

namespace App\Services;

use App\Events\UserEmailVerified;
use App\Events\UserRegistered;
use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Generate a unique account number in format XXXX-XXXX-XXXX.
     */
    public static function generateAccountNumber(): string
    {
        do {
            $segment1 = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $segment2 = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $segment3 = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $accountNumber = "{$segment1}-{$segment2}-{$segment3}";
        } while (User::query()->where('account_number', $accountNumber)->exists());

        return $accountNumber;
    }

    /**
     * @param  array{first_name: string, middle_name?: string|null, last_name: string, email: string, password: string, role?: string|null}  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $fullName = trim(($data['first_name'] ?? '').' '.($data['middle_name'] ?? '').' '.($data['last_name'] ?? ''));

        $user = User::query()->create([
            'name' => $fullName,
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'account_number' => self::generateAccountNumber(),
            'two_factor_secret' => null,
        ]);

        $allowed = config('auth.registerable_roles', ['Customer']);
        $role = $data['role'] ?? 'Customer';
        if (! in_array($role, $allowed, true)) {
            $role = 'Customer';
        }

        $user->assignRole($role);

        $verificationToken = EmailVerificationToken::createForUser($user);
        event(new UserRegistered($user, $verificationToken->token));

        $token = $user->createToken('auth')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}|null
     */
    public function login(array $credentials): ?array
    {
        if (! Auth::attempt($credentials)) {
            return null;
        }

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user) {
            return null;
        }

        if ($user->email_verified_at === null) {
            Auth::logout();

            return [
                'blocked' => true,
                'reason' => 'email_not_verified',
            ];
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Verify user email using one-time token.
     */
    public function verifyEmail(string $token): bool
    {
        $record = EmailVerificationToken::query()
            ->where('token', $token)
            ->with('user')
            ->first();

        if (! $record || ! $record->isValid()) {
            return false;
        }

        $record->user->update(['email_verified_at' => now()]);
        $record->markAsUsed();

        event(new UserEmailVerified($record->user));

        return true;
    }
}
