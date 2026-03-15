<?php

use App\Http\Controllers\Api\AdminPermissionController;
use App\Http\Controllers\Api\AdminRoleController;
use App\Http\Controllers\Api\AdminUserRoleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-login-otp', [AuthController::class, 'verifyLoginOtp']);
    Route::get('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmailWithToken']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/verify-token', [AuthController::class, 'verifyToken']);
        Route::put('/two-factor', [AuthController::class, 'enableOrUpdateTwoFactor']);
        Route::delete('/two-factor', [AuthController::class, 'disableTwoFactor']);
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

Route::middleware(['auth:sanctum', 'permission:manage roles'])->group(function (): void {
    Route::get('admin/roles/datatables', [AdminRoleController::class, 'rolesDataTables']);
    Route::get('admin/roles', [AdminRoleController::class, 'index']);
    Route::post('admin/roles', [AdminRoleController::class, 'store']);
    Route::get('admin/roles/{role}', [AdminRoleController::class, 'show']);
    Route::put('admin/roles/{role}', [AdminRoleController::class, 'update']);
    Route::patch('admin/roles/{role}', [AdminRoleController::class, 'update']);
    Route::delete('admin/roles/{role}', [AdminRoleController::class, 'destroy']);
    Route::put('admin/roles/{role}/permissions', [AdminRoleController::class, 'syncPermissions']);
    Route::patch('admin/roles/{role}/permissions', [AdminRoleController::class, 'syncPermissions']);
    Route::get('admin/permissions', [AdminPermissionController::class, 'index']);
    Route::get('admin/users', [AdminUserRoleController::class, 'index']);
    Route::put('admin/users/{user}/roles', [AdminUserRoleController::class, 'updateRoles']);
    Route::patch('admin/users/{user}/roles', [AdminUserRoleController::class, 'updateRoles']);
});

Route::get('/notifications/stream', [NotificationController::class, 'index']);

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin|Partner|view notifications|manage notifications'])->group(function (): void {
    Route::get('/notifications/datatable', [NotificationController::class, 'datatable']);
    Route::get('/notifications/unread', [NotificationController::class, 'getUnreadNotifications']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::post('/notifications/test', [NotificationController::class, 'testNotification']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{notificationId}', [NotificationController::class, 'delete']);
});

Route::post('/broadcasting/auth', [BroadcastController::class, 'authenticate'])
    ->middleware('auth:sanctum');
