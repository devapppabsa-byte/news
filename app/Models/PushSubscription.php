<?php

namespace App\Models;

use Database\Factories\PushSubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    /** @use HasFactory<PushSubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'content_encoding',
        'device_label',
        'user_agent',
        'last_seen_at',
        'expired_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<PushSubscription>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('expired_at');
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markSeen(): void
    {
        $this->forceFill(['last_seen_at' => now()])->save();
    }

    public function markExpired(): void
    {
        $this->forceFill(['expired_at' => now()])->save();
    }

    /**
     * A stable hash of the endpoint, used as a unique key so re-registering the
     * same browser updates the existing row instead of duplicating it.
     */
    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }
}
