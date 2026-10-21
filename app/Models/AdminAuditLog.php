<?php

namespace App\Models;

use Database\Factories\AdminAuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminAuditLog extends Model
{
    /** @use HasFactory<AdminAuditLogFactory> */
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function label(): string
    {
        return match ($this->action) {
            'user.suspended' => 'User suspended',
            'user.reactivated' => 'User reactivated',
            'settings.updated' => 'Global settings updated',
            'settings.reset' => 'Global settings reset',
            'mail.settings.updated' => 'Email settings updated',
            'search.settings.updated' => 'Search credentials updated',
            'ai.settings.updated' => 'AI settings updated',
            'credential.removed' => 'Credential removed',
            default => $this->action,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function actions(): array
    {
        return [
            'user.suspended',
            'user.reactivated',
            'settings.updated',
            'settings.reset',
            'mail.settings.updated',
            'search.settings.updated',
            'ai.settings.updated',
            'credential.removed',
        ];
    }
}
