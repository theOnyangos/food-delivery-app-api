<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $result['user'],
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if ($result === null) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (isset($result['blocked']) && $result['blocked']) {
            $message = match ($result['reason']) {
                'email_not_verified' => 'Please verify your email address.',
                default => 'Access denied.',
            };

            return response()->json([
                'message' => $message,
                'reason' => $result['reason'],
            ], 403);
        }

        return response()->json([
            'message' => 'Login successful.',
            'user' => $result['user'],
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function verifyToken(Request $request): JsonResponse
    {
        return response()->json([
            'valid' => true,
            'message' => 'Token is valid.',
            'user_id' => $request->user()->id,
        ]);
    }

    /**
     * Verify account email using one-time token (query or body).
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->query('token') ?? $request->input('token');

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Verification token is required.',
            ], 422);
        }

        if (! $this->authService->verifyEmail($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification token. Please request a new verification email.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your email has been verified successfully.',
        ]);
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
                return response()->json(['success' => false, 'message' => $message], 400);
            }

            return view('auth.verify-email-result', [
                'success' => false,
                'message' => $message,
                'loginUrl' => $loginUrl,
            ]);
        }

        $message = 'Your email has been verified successfully.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return view('auth.verify-email-result', [
            'success' => true,
            'message' => $message,
            'loginUrl' => $loginUrl,
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->validated());

        return response()->json([
            'message' => 'If an account exists with that email, we have sent a password reset link.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 400);
        }

        return response()->json([
            'message' => 'Your password has been reset successfully.',
        ]);
    }
}
