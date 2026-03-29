<?php

namespace App\Http\Controllers\Api;

use App\Events\PasswordResetLinkRequested;
use App\Events\UserEmailVerified;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateTwoFactorRequest;
use App\Http\Requests\Auth\VerifyLoginOtpRequest;
use App\Services\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const OTP_MAX_ATTEMPTS = 5;

    private const OTP_DECAY_SECONDS = 300;

    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->apiSuccess([
            'user' => $result['user'],
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'two_factor_enabled' => false,
            'roles' => $result['roles'] ?? [],
        ], 'Registration successful.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if ($result === null) {
            return $this->apiError('Invalid credentials.', 401);
        }

        if (isset($result['blocked']) && $result['blocked']) {
            $message = match ($result['reason']) {
                'email_not_verified' => 'Please verify your email address.',
                'account_blocked' => 'Your account has been suspended. Contact support if you need help.',
                default => 'Access denied.',
            };

            return $this->apiError($message, 403, ['reason' => $result['reason']]);
        }

        if (isset($result['requires_two_factor']) && $result['requires_two_factor']) {
            return $this->apiSuccess([
                'requires_two_factor' => true,
                'two_factor_enabled' => true,
                'otp_challenge_token' => $result['otp_challenge_token'],
                'otp_expires_in' => $result['otp_expires_in'],
                'roles' => $result['roles'] ?? [],
                'user' => [
                    'id' => $result['user']->id,
                    'email' => $result['user']->email,
                    'full_name' => $result['user']->full_name,
                ],
            ], 'OTP verification required.');
        }

        return $this->apiSuccess([
            'user' => $result['user']->withoutRelations(),
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'two_factor_enabled' => (bool) ($result['two_factor_enabled'] ?? false),
            'roles' => $result['roles'] ?? [],
        ], 'Login successful.');
    }

    public function verifyLoginOtp(VerifyLoginOtpRequest $request): JsonResponse
    {
        $rateLimitKey = sprintf(
            'auth:verify-login-otp:%s:%s',
            $request->input('otp_challenge_token'),
            (string) $request->ip()
        );

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::OTP_MAX_ATTEMPTS)) {
            return $this->apiError('Too many OTP attempts. Please wait before trying again.', 429, [
                'retry_after' => RateLimiter::availableIn($rateLimitKey),
            ]);
        }

        $result = $this->authService->verifyLoginOtp($request->validated());

        if ($result === null) {
            RateLimiter::hit($rateLimitKey, self::OTP_DECAY_SECONDS);

            return $this->apiError('OTP verification failed.');
        }

        if (isset($result['blocked']) && $result['blocked']) {
            $status = $result['reason'] === 'invalid_otp' ? 422 : 400;
            $message = match ($result['reason']) {
                'invalid_otp' => 'Invalid OTP code.',
                'otp_challenge_expired' => 'OTP challenge has expired. Please login again.',
                'otp_challenge_invalid' => 'Invalid OTP challenge. Please login again.',
                'two_factor_not_enabled' => 'Two-factor authentication is not enabled for this user.',
                'account_blocked' => 'Your account has been suspended. Contact support if you need help.',
                default => 'OTP verification failed.',
            };

            RateLimiter::hit($rateLimitKey, self::OTP_DECAY_SECONDS);

            return $this->apiError($message, $status, ['reason' => $result['reason']]);
        }

        RateLimiter::clear($rateLimitKey);

        return $this->apiSuccess([
            'user' => $result['user']->withoutRelations(),
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'two_factor_enabled' => true,
            'roles' => $result['roles'] ?? [],
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->apiSuccess(null, 'Logged out successfully.');
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->apiSuccess([
            'user' => $user,
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            'roles' => $user->getRoleNames()->values(),
        ], 'User fetched successfully.');
    }

    public function verifyToken(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->apiSuccess([
            'valid' => true,
            'user_id' => $user->id,
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            'roles' => $user->getRoleNames()->values(),
        ], 'Token is valid.');
    }

    public function enableOrUpdateTwoFactor(UpdateTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->authService->enableOrUpdateTwoFactor($user, $request->validated('otp'));

        return $this->apiSuccess([
            'two_factor_enabled' => true,
            'roles' => $user->getRoleNames()->values(),
        ], 'Two-factor authentication has been enabled/updated.');
    }

    public function disableTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authService->disableTwoFactor($user);

        return $this->apiSuccess([
            'two_factor_enabled' => false,
            'roles' => $user->getRoleNames()->values(),
        ], 'Two-factor authentication has been disabled.');
    }

    /**
     * Verify account email using one-time token (query or body).
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->query('token') ?? $request->input('token');

        if (empty($token)) {
            return $this->apiError('Verification token is required.', 422);
        }

        if (! $this->authService->verifyEmail($token)) {
            return $this->apiError('Invalid or expired verification token. Please request a new verification email.');
        }

        return $this->apiSuccess(null, 'Your email has been verified successfully.');
    }

    /**
     * Verify account email using token from URL path.
     */
    public function verifyEmailWithToken(Request $request, string $token): JsonResponse|View
    {
        $verified = $this->authService->verifyEmail($token);
        $loginUrl = config('app.frontend_login_url', '/login');

        if (! $verified) {
            $message = 'Invalid or expired verification token. Please request a new verification email.';

            if ($request->expectsJson()) {
                return $this->apiError($message);
            }

            return view('auth.verify-email-result', [
                'success' => false,
                'message' => $message,
                'loginUrl' => $loginUrl,
            ]);
        }

        $message = 'Your email has been verified successfully.';

        if ($request->expectsJson()) {
            return $this->apiSuccess(null, $message);
        }

        return view('auth.verify-email-result', [
            'success' => true,
            'message' => $message,
            'loginUrl' => $loginUrl,
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        event(new PasswordResetLinkRequested($request->validated()['email']));

        return $this->apiSuccess(null, 'If an account exists with that email, you will receive a password reset link shortly.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function ($user, string $password): void {
                $wasUnverified = $user->email_verified_at === null;

                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                if ($wasUnverified) {
                    event(new UserEmailVerified($user));
                }

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->apiError(__($status));
        }

        return $this->apiSuccess(null, 'Your password has been reset successfully.');
    }
}
