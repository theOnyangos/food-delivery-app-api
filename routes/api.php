<?php

use App\Http\Controllers\Api\AdminPermissionController;
use App\Http\Controllers\Api\AdminRoleController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminUserRoleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeliveryAddressController;
use App\Http\Controllers\Api\DeliveryZoneController;
use App\Http\Controllers\Api\MealCategoryController;
use App\Http\Controllers\Api\MealController;
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
    Route::put('admin/users/{user}/roles', [AdminUserRoleController::class, 'updateRoles']);
    Route::patch('admin/users/{user}/roles', [AdminUserRoleController::class, 'updateRoles']);
});

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|manage users'])->group(function (): void {
    Route::get('admin/users/role-options', [AdminUserController::class, 'roleOptions']);
    Route::get('admin/users', [AdminUserRoleController::class, 'index']);
    Route::post('admin/users', [AdminUserController::class, 'store']);
    Route::post('admin/users/{user}/resend-invite', [AdminUserController::class, 'resendInvite']);
});

Route::get('/notifications/stream', [NotificationController::class, 'index']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/notifications/preferences', [NotificationController::class, 'getPreferences']);
    Route::put('/notifications/preferences', [NotificationController::class, 'updatePreferences']);
    Route::patch('/notifications/preferences', [NotificationController::class, 'updatePreferences']);
});

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

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin'])->prefix('admin/delivery-zones')->group(function (): void {
    Route::get('/', [DeliveryZoneController::class, 'index']);
    Route::post('/', [DeliveryZoneController::class, 'store']);
    Route::get('/{deliveryZone}', [DeliveryZoneController::class, 'show']);
    Route::put('/{deliveryZone}', [DeliveryZoneController::class, 'update']);
    Route::patch('/{deliveryZone}', [DeliveryZoneController::class, 'update']);
    Route::delete('/{deliveryZone}', [DeliveryZoneController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/delivery-zones/check-coverage', [DeliveryZoneController::class, 'checkCoverage']);

    Route::prefix('delivery-addresses')->group(function (): void {
        Route::get('/', [DeliveryAddressController::class, 'index']);
        Route::post('/', [DeliveryAddressController::class, 'store']);
        Route::get('/{deliveryAddress}', [DeliveryAddressController::class, 'show']);
        Route::put('/{deliveryAddress}', [DeliveryAddressController::class, 'update']);
        Route::patch('/{deliveryAddress}', [DeliveryAddressController::class, 'update']);
        Route::delete('/{deliveryAddress}', [DeliveryAddressController::class, 'destroy']);
    });

    Route::get('/meals', [MealController::class, 'index']);
    Route::get('/meals/{meal}', [MealController::class, 'show']);
    Route::get('/meal-categories', [MealCategoryController::class, 'index']);
    Route::get('/meal-categories/{mealCategory}', [MealCategoryController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin|Partner'])->group(function (): void {
    Route::get('/my-meals', [MealController::class, 'myMeals']);
    Route::post('/my-meals', [MealController::class, 'store']);
    Route::get('/my-meals/{meal}', [MealController::class, 'showMine']);
    Route::put('/my-meals/{meal}', [MealController::class, 'update']);
    Route::patch('/my-meals/{meal}', [MealController::class, 'update']);
    Route::delete('/my-meals/{meal}', [MealController::class, 'destroy']);
    Route::put('/my-meals/{meal}/nutrition', [MealController::class, 'upsertNutrition']);
    Route::patch('/my-meals/{meal}/nutrition', [MealController::class, 'upsertNutrition']);
    Route::put('/my-meals/{meal}/allergens', [MealController::class, 'syncAllergens']);
    Route::patch('/my-meals/{meal}/allergens', [MealController::class, 'syncAllergens']);
    Route::put('/my-meals/{meal}/ingredients', [MealController::class, 'syncIngredients']);
    Route::patch('/my-meals/{meal}/ingredients', [MealController::class, 'syncIngredients']);
    Route::put('/my-meals/{meal}/recipes', [MealController::class, 'syncRecipes']);
    Route::patch('/my-meals/{meal}/recipes', [MealController::class, 'syncRecipes']);
    Route::put('/my-meals/{meal}/tutorials', [MealController::class, 'syncTutorials']);
    Route::patch('/my-meals/{meal}/tutorials', [MealController::class, 'syncTutorials']);
});

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin'])->group(function (): void {
    Route::post('/meal-categories', [MealCategoryController::class, 'store']);
    Route::put('/meal-categories/{mealCategory}', [MealCategoryController::class, 'update']);
    Route::patch('/meal-categories/{mealCategory}', [MealCategoryController::class, 'update']);
    Route::delete('/meal-categories/{mealCategory}', [MealCategoryController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function (): void {
    Route::post('/admin/cache/redis/clear', [MealController::class, 'clearRedisCache']);
});
