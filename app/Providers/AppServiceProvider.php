<?php

namespace App\Providers;

use App\Events\UserEmailVerified;
use App\Events\UserRegistered;
use App\Listeners\CreateRegistrationNotification;
use App\Listeners\SendVerificationNotification;
use App\Listeners\SendWelcomeNotification;
use App\Models\PersonalAccessToken;
use App\Services\RedisService;
use Illuminate\Support\Facades\Event;
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

        Event::listen(UserRegistered::class, SendVerificationNotification::class);
        Event::listen(UserRegistered::class, CreateRegistrationNotification::class);
        Event::listen(UserEmailVerified::class, SendWelcomeNotification::class);
    }
}
