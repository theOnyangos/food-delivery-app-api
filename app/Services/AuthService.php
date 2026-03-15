<?php

namespace App\Services;

use App\Events\UserEmailVerified;
use App\Events\UserRegistered;
use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private const LOGIN_OTP_TTL_SECONDS = 300;

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
        $user = User::query()->create([
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
     * @return array<string, mixed>|null
     */
    public function login(array $credentials): ?array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], (string) $user->password)) {
            return null;
        }

        if ($user->email_verified_at === null) {
            return [
                'blocked' => true,
                'reason' => 'email_not_verified',
            ];
        }

        $roles = $user->getRoleNames()->values()->all();

        if ($user->hasTwoFactorEnabled()) {
            $challengeToken = Str::random(64);

            Cache::put($this->loginOtpCacheKey($challengeToken), [
                'user_id' => $user->id,
            ], now()->addSeconds(self::LOGIN_OTP_TTL_SECONDS));

            return [
                'requires_two_factor' => true,
                'two_factor_enabled' => true,
                'otp_challenge_token' => $challengeToken,
                'otp_expires_in' => self::LOGIN_OTP_TTL_SECONDS,
                'roles' => $roles,
                'user' => $user,
            ];
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'roles' => $roles,
            'two_factor_enabled' => false,
        ];
    }

    /**
     * @param  array{otp_challenge_token: string, otp: string}  $payload
     * @return array<string, mixed>|null
     */
    public function verifyLoginOtp(array $payload): ?array
    {
        $challenge = Cache::get($this->loginOtpCacheKey($payload['otp_challenge_token']));

        if (! is_array($challenge) || empty($challenge['user_id'])) {
            return [
                'blocked' => true,
                'reason' => 'otp_challenge_expired',
            ];
        }

        $user = User::query()->find($challenge['user_id']);

        if (! $user) {
            Cache::forget($this->loginOtpCacheKey($payload['otp_challenge_token']));

            return [
                'blocked' => true,
                'reason' => 'otp_challenge_invalid',
            ];
        }

        if (! $user->hasTwoFactorEnabled()) {
            Cache::forget($this->loginOtpCacheKey($payload['otp_challenge_token']));

            return [
                'blocked' => true,
                'reason' => 'two_factor_not_enabled',
            ];
        }

        if (! $this->matchesOtp($payload['otp'], (string) $user->two_factor_secret)) {
            return [
                'blocked' => true,
                'reason' => 'invalid_otp',
            ];
        }

        Cache::forget($this->loginOtpCacheKey($payload['otp_challenge_token']));

        $user->tokens()->delete();
        $token = $user->createToken('auth')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'roles' => $user->getRoleNames()->values()->all(),
            'two_factor_enabled' => true,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function enableOrUpdateTwoFactor(User $user, string $otp): void
    {
        $user->forceFill([
            'two_factor_secret' => Hash::make($otp),
        ])->save();
    }

    public function disableTwoFactor(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
        ])->save();
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

    private function loginOtpCacheKey(string $challengeToken): string
    {
        return 'auth:login-otp:'.$challengeToken;
    }

    private function matchesOtp(string $otp, string $storedSecret): bool
    {
        if ($storedSecret === '') {
            return false;
        }

        if (str_starts_with($storedSecret, '$2y$') || str_starts_with($storedSecret, '$argon2')) {
            return Hash::check($otp, $storedSecret);
        }

        return hash_equals($storedSecret, $otp);
    }
}
