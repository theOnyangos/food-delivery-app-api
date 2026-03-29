<?php

namespace App\Providers;

use App\Events\PasswordResetLinkRequested;
use App\Events\UserBlockedByAdmin;
use App\Events\UserDeletedByAdmin;
use App\Events\UserUnblockedByAdmin;
use App\Events\UserEmailVerified;
use App\Events\UserRegistered;
use App\Listeners\CreateRegistrationNotification;
use App\Listeners\QueueUserBlockedNotification;
use App\Listeners\QueueUserDeletedNotification;
use App\Listeners\QueueUserUnblockedNotification;
use App\Listeners\SendPasswordResetLinkListener;
use App\Listeners\SendVerificationNotification;
use App\Listeners\SendWelcomeNotification;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\PersonalAccessToken;
use App\Services\RedisService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RedisService::class, function () {
            return new RedisService(config('cache.default'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        ResetPassword::createUrlUsing(function ($user, string $token): string {
            $base = rtrim((string) config('app.client_url'), '/');

            return $base.'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });

        Event::listen(PasswordResetLinkRequested::class, SendPasswordResetLinkListener::class);
        Event::listen(UserBlockedByAdmin::class, QueueUserBlockedNotification::class);
        Event::listen(UserUnblockedByAdmin::class, QueueUserUnblockedNotification::class);
        Event::listen(UserDeletedByAdmin::class, QueueUserDeletedNotification::class);
        Event::listen(UserRegistered::class, SendVerificationNotification::class);
        Event::listen(UserRegistered::class, CreateRegistrationNotification::class);
        Event::listen(UserEmailVerified::class, SendWelcomeNotification::class);

        Route::bind('blog', function (string $value): Blog {
            return Blog::query()
                ->where('slug', $value)
                ->orWhere('id', $value)
                ->firstOrFail();
        });

        Route::bind('blog_category', function (string $value): BlogCategory {
            return BlogCategory::query()
                ->where('slug', $value)
                ->orWhere('id', $value)
                ->firstOrFail();
        });
    }
}
