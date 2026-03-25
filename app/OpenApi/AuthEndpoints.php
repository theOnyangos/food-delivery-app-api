<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/auth/register',
    operationId: 'registerUser',
    tags: ['Auth'],
    summary: 'Register a user',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['first_name', 'last_name', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                new OA\Property(property: 'middle_name', type: 'string', nullable: true, example: 'K'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                new OA\Property(property: 'role', type: 'string', enum: ['Customer', 'Partner'], example: 'Customer'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret1234'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'secret1234'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Registration successful'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Post(
    path: '/api/auth/login',
    operationId: 'loginUser',
    tags: ['Auth'],
    summary: 'Login user (returns challenge if 2FA enabled)',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret1234'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Login successful or OTP challenge required when 2FA is enabled'),
        new OA\Response(response: 403, description: 'Email not verified'),
        new OA\Response(response: 401, description: 'Invalid credentials'),
    ]
)]
#[OA\Post(
    path: '/api/auth/verify-login-otp',
    operationId: 'verifyLoginOtp',
    tags: ['Auth'],
    summary: 'Verify login OTP and complete authentication',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['otp_challenge_token', 'otp'],
            properties: [
                new OA\Property(property: 'otp_challenge_token', type: 'string', example: 'challenge-token-from-login-response'),
                new OA\Property(property: 'otp', type: 'string', example: '123456'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'OTP verified and login successful'),
        new OA\Response(response: 422, description: 'Invalid OTP code'),
        new OA\Response(response: 400, description: 'Invalid or expired challenge'),
        new OA\Response(response: 429, description: 'Too many OTP attempts'),
    ]
)]
#[OA\Get(
    path: '/api/auth/verify-email',
    operationId: 'verifyEmailByQuery',
    tags: ['Auth'],
    summary: 'Verify account email by token query',
    parameters: [
        new OA\Parameter(name: 'token', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Email verified'),
        new OA\Response(response: 400, description: 'Invalid or expired token'),
        new OA\Response(response: 422, description: 'Missing token'),
    ]
)]
#[OA\Post(
    path: '/api/auth/verify-email',
    operationId: 'verifyEmailByBody',
    tags: ['Auth'],
    summary: 'Verify account email by token body',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['token'],
            properties: [
                new OA\Property(property: 'token', type: 'string'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Email verified'),
        new OA\Response(response: 400, description: 'Invalid or expired token'),
        new OA\Response(response: 422, description: 'Missing token'),
    ]
)]
#[OA\Get(
    path: '/api/auth/verify-email/{token}',
    operationId: 'verifyEmailByPath',
    tags: ['Auth'],
    summary: 'Verify account email by token path',
    parameters: [
        new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Email verified'),
        new OA\Response(response: 400, description: 'Invalid or expired token'),
    ]
)]
#[OA\Post(
    path: '/api/auth/forgot-password',
    operationId: 'forgotPassword',
    tags: ['Auth'],
    summary: 'Request password reset link',
    description: 'Dispatches a queued job to email a reset link. Response is immediate; delivery uses CLIENT_URL for the SPA link.',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Accepted. If an account exists for that email, a reset link will be sent shortly.'),
    ]
)]
#[OA\Post(
    path: '/api/auth/reset-password',
    operationId: 'resetPassword',
    tags: ['Auth'],
    summary: 'Reset password with token',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'token', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                new OA\Property(property: 'token', type: 'string', example: 'reset-token-here'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'newsecret1234'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'newsecret1234'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Password reset successful'),
        new OA\Response(response: 400, description: 'Invalid or expired token'),
    ]
)]
#[OA\Post(
    path: '/api/auth/logout',
    operationId: 'logoutUser',
    tags: ['Auth'],
    summary: 'Logout current user',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Logged out successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/auth/user',
    operationId: 'getAuthenticatedUser',
    tags: ['Auth'],
    summary: 'Get authenticated user',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Authenticated user response'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/auth/verify-token',
    operationId: 'verifyToken',
    tags: ['Auth'],
    summary: 'Verify current auth token',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Token is valid'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Put(
    path: '/api/auth/two-factor',
    operationId: 'enableOrUpdateTwoFactor',
    tags: ['Auth'],
    summary: 'Enable or update login OTP for two-factor authentication',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['otp', 'otp_confirmation'],
            properties: [
                new OA\Property(property: 'otp', type: 'string', example: '123456'),
                new OA\Property(property: 'otp_confirmation', type: 'string', example: '123456'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Two-factor updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Delete(
    path: '/api/auth/two-factor',
    operationId: 'disableTwoFactor',
    tags: ['Auth'],
    summary: 'Disable two-factor authentication',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Two-factor disabled successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
class AuthEndpoints {}
