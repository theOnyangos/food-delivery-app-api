<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleManagementController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmailWithToken']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/verify-token', [AuthController::class, 'verifyToken']);
    });
});

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin|Partner'])->prefix('uploads')->group(function (): void {
    Route::post('/image', [UploadController::class, 'image']);
    Route::post('/public-asset', [UploadController::class, 'publicAsset']);
    Route::post('/private-asset', [UploadController::class, 'privateAsset']);
    Route::get('/{media}/url', [UploadController::class, 'serveUrl']);
    Route::delete('/{media}', [UploadController::class, 'destroy']);
    Route::delete('/', [UploadController::class, 'destroyByPath']);
});

Route::get('/uploads/serve/{media}', [UploadController::class, 'serve'])
    ->name('uploads.serve')
    ->middleware('signed');

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin'])->prefix('admin')->group(function (): void {
    Route::get('/roles', [RoleManagementController::class, 'index']);
    Route::patch('/users/{user}/role', [RoleManagementController::class, 'assign']);
});

Route::post('/broadcasting/auth', [BroadcastController::class, 'authenticate'])
    ->middleware('auth:sanctum');
