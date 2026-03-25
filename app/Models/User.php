<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\QueuedResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable;

    protected $table = 'asl_users';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'account_number',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFullNameAttribute(): string
    {
        $parts = [
            trim((string) ($this->attributes['first_name'] ?? '')),
            trim((string) ($this->attributes['middle_name'] ?? '')),
            trim((string) ($this->attributes['last_name'] ?? '')),
        ];

        return implode(' ', array_values(array_filter($parts, fn (string $part): bool => $part !== '')));
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->two_factor_secret);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function deliveryAddresses(): HasMany
    {
        return $this->hasMany(DeliveryAddress::class);
    }

    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new QueuedResetPasswordNotification($token));
    }

    /**
     * Whether the user can use the staff/partner AI chat (not the assistant popup daily limit path).
     */
    public function hasAiAssistantAccess(): bool
    {
        if ($this->hasAnyRole(['Super Admin', 'Admin', 'Partner'])) {
            return true;
        }

        return $this->can('use ai chat');
    }

    /**
     * Daily AI message limit for this user. Staff use config; partners use partner/customer config fallback.
     */
    public function getAiAssistantDailyLimit(): int
    {
        if ($this->hasAnyRole(['Super Admin', 'Admin'])) {
            return (int) config('ai_agent.daily_limit_admin', 0);
        }
        if ($this->hasRole('Partner')) {
            $partner = (int) config('ai_agent.daily_limit_partner', 0);

            return $partner > 0 ? $partner : (int) config('ai_agent.daily_limit_customer', 5);
        }

        return (int) config('ai_agent.daily_limit_customer', 5);
    }

    /**
     * Live support chat: staff always; partners need permission; customers may be granted for future flows.
     */
    public function hasLiveChatAccess(): bool
    {
        if ($this->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        return $this->can('use live chat');
    }
}
