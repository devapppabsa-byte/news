<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'suspended_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'role' => UserRole::class,
            'status' => AccountStatus::class,
            'suspended_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === AccountStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === AccountStatus::Suspended;
    }

    public function isPendingVerification(): bool
    {
        return $this->status === AccountStatus::PendingVerification;
    }

    public function suspend(): void
    {
        $this->forceFill([
            'status' => AccountStatus::Suspended,
            'suspended_at' => now(),
        ])->save();
    }

    public function reactivate(): void
    {
        $this->forceFill([
            'status' => AccountStatus::Active,
            'suspended_at' => null,
        ])->save();
    }

    /**
     * @return HasMany<MonitoredTopic, $this>
     */
    public function monitoredTopics(): HasMany
    {
        return $this->hasMany(MonitoredTopic::class);
    }

    /**
     * @return HasOne<Preference, $this>
     */
    public function preference(): HasOne
    {
        return $this->hasOne(Preference::class);
    }

    /**
     * @return HasMany<NewsItem, $this>
     */
    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return HasMany<NotificationDelivery, $this>
     */
    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    /**
     * @return HasMany<PushSubscription, $this>
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * @return BelongsToMany<MonitoredTopic, $this>
     */
    public function subscribedTopics(): BelongsToMany
    {
        return $this->belongsToMany(MonitoredTopic::class, 'subscriptions', 'user_id', 'topic_id')
            ->withTimestamps();
    }
}
