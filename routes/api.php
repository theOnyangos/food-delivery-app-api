<?php

use App\Http\Controllers\Api\AdminAiAgentConfigController;
use App\Http\Controllers\Api\AdminAiAgentConversationController;
use App\Http\Controllers\Api\AdminAiAgentKbController;
use App\Http\Controllers\Api\AdminBlogCategoryController;
use App\Http\Controllers\Api\AdminBlogController;
use App\Http\Controllers\Api\AdminNewsletterController;
use App\Http\Controllers\Api\AdminPermissionController;
use App\Http\Controllers\Api\AdminReviewCategoryController;
use App\Http\Controllers\Api\AdminReviewController;
use App\Http\Controllers\Api\AdminReviewTopicController;
use App\Http\Controllers\Api\AdminRoleController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminUserRoleController;
use App\Http\Controllers\Api\AIAgentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChatSettingsController;
use App\Http\Controllers\Api\ChatSupportAllocationController;
use App\Http\Controllers\Api\ChatTypingController;
use App\Http\Controllers\Api\DeliveryAddressController;
use App\Http\Controllers\Api\DeliveryZoneController;
use App\Http\Controllers\Api\MealCategoryController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\MealReviewController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PartnerMealReviewController;
use App\Http\Controllers\Api\PublicBlogController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserReviewController;
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

Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);

Route::get('/blog/categories', [PublicBlogController::class, 'categories']);
Route::get('/blogs/recent', [PublicBlogController::class, 'recent']);
Route::get('/blogs', [PublicBlogController::class, 'index']);
Route::get('/blogs/{slugOrId}', [PublicBlogController::class, 'show']);

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin|Partner|manage uploads'])->prefix('uploads')->group(function (): void {
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

Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('chat')->group(function (): void {
        Route::middleware('permission:manage chat')->group(function (): void {
            Route::get('settings', [ChatSettingsController::class, 'getSettings']);
            Route::put('settings', [ChatSettingsController::class, 'updateSettings']);
            Route::get('support-allocations', [ChatSupportAllocationController::class, 'index']);
            Route::post('support-allocations', [ChatSupportAllocationController::class, 'store']);
            Route::delete('support-allocations/{id}', [ChatSupportAllocationController::class, 'destroy']);
            Route::get('vendor-users', [ChatController::class, 'indexVendorUsers']);
            Route::delete('conversations/{id}', [ChatController::class, 'destroyConversation']);
        });
        Route::middleware('role_or_permission:Super Admin|Admin|Partner|Customer|use live chat|manage chat')->group(function (): void {
            Route::get('conversations', [ChatController::class, 'indexConversations']);
            Route::post('conversations', [ChatController::class, 'storeConversation']);
            Route::get('conversations/{id}', [ChatController::class, 'showConversation']);
            Route::get('conversations/{conversationId}/messages', [ChatController::class, 'indexMessages']);
            Route::post('conversations/{conversationId}/messages', [ChatController::class, 'storeMessage']);
            Route::patch('conversations/{conversationId}/read', [ChatController::class, 'markRead']);
            Route::get('conversations/{conversationId}/attachments/{mediaId}/serve-url', [ChatController::class, 'getAttachmentServeUrl']);
            Route::post('conversations/{conversationId}/typing', [ChatTypingController::class, 'store']);
        });
    });
});

Route::middleware(['auth:sanctum', 'permission:manage delivery zones'])->prefix('admin/delivery-zones')->group(function (): void {
    Route::get('/', [DeliveryZoneController::class, 'index']);
    Route::post('/', [DeliveryZoneController::class, 'store']);
    Route::get('/{deliveryZone}', [DeliveryZoneController::class, 'show']);
    Route::put('/{deliveryZone}', [DeliveryZoneController::class, 'update']);
    Route::patch('/{deliveryZone}', [DeliveryZoneController::class, 'update']);
    Route::delete('/{deliveryZone}', [DeliveryZoneController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/delivery-zones/check-coverage', [DeliveryZoneController::class, 'checkCoverage']);

    Route::get('/meals/reviews', [MealReviewController::class, 'all']);
    Route::get('/meals/{meal}/reviews', [MealReviewController::class, 'index']);
    Route::post('/meals/{meal}/reviews', [MealReviewController::class, 'store']);
    Route::get('/meals', [MealController::class, 'index']);
    Route::get('/meals/{meal}', [MealController::class, 'show']);
    Route::get('/meal-categories', [MealCategoryController::class, 'index']);
    Route::get('/meal-categories/{mealCategory}', [MealCategoryController::class, 'show']);

    Route::get('/user/reviews', [UserReviewController::class, 'index']);
    Route::delete('/user/reviews/{review}', [UserReviewController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin|Partner|Customer|manage delivery addresses'])->group(function (): void {
    Route::prefix('delivery-addresses')->group(function (): void {
        Route::get('/', [DeliveryAddressController::class, 'index']);
        Route::post('/', [DeliveryAddressController::class, 'store']);
        Route::get('/{deliveryAddress}', [DeliveryAddressController::class, 'show']);
        Route::put('/{deliveryAddress}', [DeliveryAddressController::class, 'update']);
        Route::patch('/{deliveryAddress}', [DeliveryAddressController::class, 'update']);
        Route::delete('/{deliveryAddress}', [DeliveryAddressController::class, 'destroy']);
    });
});

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin|Partner|manage meals'])->group(function (): void {
    Route::get('/my-meals', [MealController::class, 'myMeals']);
    Route::post('/my-meals', [MealController::class, 'store']);
    Route::get('/my-meals/{meal}/reviews', [PartnerMealReviewController::class, 'index']);
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

Route::middleware(['auth:sanctum', 'permission:manage meal categories'])->group(function (): void {
    Route::post('/meal-categories', [MealCategoryController::class, 'store']);
    Route::put('/meal-categories/{mealCategory}', [MealCategoryController::class, 'update']);
    Route::patch('/meal-categories/{mealCategory}', [MealCategoryController::class, 'update']);
    Route::delete('/meal-categories/{mealCategory}', [MealCategoryController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function (): void {
    Route::post('/admin/cache/redis/clear', [MealController::class, 'clearRedisCache']);
});

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin|Partner|use ai chat'])->prefix('ai')->group(function (): void {
    Route::post('chat', [AIAgentController::class, 'chat']);
    Route::get('conversations', [AIAgentController::class, 'getConversations']);
    Route::get('conversations/{id}', [AIAgentController::class, 'getConversation']);
    Route::delete('conversations/{id}', [AIAgentController::class, 'deleteConversation']);
    Route::post('conversations/{id}/regenerate', [AIAgentController::class, 'regenerate']);
    Route::post('assistant/chat', [AIAgentController::class, 'assistantChat']);
    Route::get('assistant/conversations', [AIAgentController::class, 'getAssistantConversations']);
    Route::get('assistant/conversations/{id}', [AIAgentController::class, 'getAssistantConversation']);
    Route::delete('assistant/conversations/{id}', [AIAgentController::class, 'deleteAssistantConversation']);
});

Route::middleware(['auth:sanctum', 'permission:manage ai agent'])->prefix('admin/ai-agent')->group(function (): void {
    Route::get('config', [AdminAiAgentConfigController::class, 'show']);
    Route::put('config', [AdminAiAgentConfigController::class, 'update']);
    Route::get('openai/models', [AdminAiAgentConfigController::class, 'listOpenAIModels']);
    Route::get('openai/assistants', [AdminAiAgentConfigController::class, 'listOpenAIAssistants']);
    Route::get('conversations/stats', [AdminAiAgentConversationController::class, 'stats']);
    Route::get('conversations', [AdminAiAgentConversationController::class, 'index']);
    Route::get('conversations/{id}', [AdminAiAgentConversationController::class, 'show']);
    Route::get('kb/sources', [AdminAiAgentKbController::class, 'index']);
    Route::post('kb/sources', [AdminAiAgentKbController::class, 'store']);
    Route::post('kb/ingest-all', [AdminAiAgentKbController::class, 'ingestAll']);
    Route::get('kb/sources/{id}', [AdminAiAgentKbController::class, 'show']);
    Route::put('kb/sources/{id}', [AdminAiAgentKbController::class, 'update']);
    Route::patch('kb/sources/{id}', [AdminAiAgentKbController::class, 'update']);
    Route::delete('kb/sources/{id}', [AdminAiAgentKbController::class, 'destroy']);
    Route::post('kb/sources/{id}/ingest', [AdminAiAgentKbController::class, 'ingest']);
});

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|manage newsletter', 'permission:manage newsletter'])->prefix('admin/newsletter')->group(function (): void {
    Route::post('/send', [AdminNewsletterController::class, 'send']);
    Route::get('/subscribers', [AdminNewsletterController::class, 'index']);
    Route::get('/subscribers/{subscriber}', [AdminNewsletterController::class, 'show']);
    Route::patch('/subscribers/{subscriber}/unsubscribe', [AdminNewsletterController::class, 'unsubscribe']);
    Route::delete('/subscribers/{subscriber}', [AdminNewsletterController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role_or_permission:Super Admin|Admin|manage content', 'permission:manage content'])->group(function (): void {
    Route::prefix('admin/blog')->group(function (): void {
        Route::get('/categories', [AdminBlogCategoryController::class, 'index']);
        Route::post('/categories', [AdminBlogCategoryController::class, 'store']);
        Route::get('/categories/{blog_category}', [AdminBlogCategoryController::class, 'show']);
        Route::put('/categories/{blog_category}', [AdminBlogCategoryController::class, 'update']);
        Route::patch('/categories/{blog_category}', [AdminBlogCategoryController::class, 'update']);
        Route::delete('/categories/{blog_category}', [AdminBlogCategoryController::class, 'destroy']);
    });
    Route::prefix('admin/blogs')->group(function (): void {
        Route::get('/', [AdminBlogController::class, 'index']);
        Route::post('/', [AdminBlogController::class, 'store']);
        Route::get('/{blog}', [AdminBlogController::class, 'show']);
        Route::put('/{blog}', [AdminBlogController::class, 'update']);
        Route::patch('/{blog}', [AdminBlogController::class, 'update']);
        Route::delete('/{blog}', [AdminBlogController::class, 'destroy']);
    });
});

Route::middleware(['auth:sanctum', 'permission:manage review categories'])->prefix('admin/review-categories')->group(function (): void {
    Route::get('/', [AdminReviewCategoryController::class, 'index']);
    Route::get('/list', [AdminReviewCategoryController::class, 'list']);
    Route::post('/', [AdminReviewCategoryController::class, 'store']);
    Route::get('/{review_category}', [AdminReviewCategoryController::class, 'show']);
    Route::put('/{review_category}', [AdminReviewCategoryController::class, 'update']);
    Route::patch('/{review_category}', [AdminReviewCategoryController::class, 'update']);
    Route::delete('/{review_category}', [AdminReviewCategoryController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'permission:manage review topics'])->prefix('admin/review-topics')->group(function (): void {
    Route::get('/', [AdminReviewTopicController::class, 'index']);
    Route::get('/list', [AdminReviewTopicController::class, 'list']);
    Route::post('/', [AdminReviewTopicController::class, 'store']);
    Route::get('/{review_topic}', [AdminReviewTopicController::class, 'show']);
    Route::put('/{review_topic}', [AdminReviewTopicController::class, 'update']);
    Route::patch('/{review_topic}', [AdminReviewTopicController::class, 'update']);
    Route::delete('/{review_topic}', [AdminReviewTopicController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'permission:manage reviews'])->prefix('admin/reviews')->group(function (): void {
    Route::get('/', [AdminReviewController::class, 'index']);
    Route::get('/{review}', [AdminReviewController::class, 'show']);
    Route::patch('/{review}/status', [AdminReviewController::class, 'updateStatus']);
    Route::delete('/{review}', [AdminReviewController::class, 'destroy']);
});
