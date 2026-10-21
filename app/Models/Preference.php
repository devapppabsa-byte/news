<?php

namespace App\Models;

use App\Enums\NotificationMode;
use Database\Factories\PreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Preference extends Model
{
    /** @use HasFactory<PreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'locale',
        'timezone',
        'email_notifications',
        'notification_mode',
        'push_notifications',
        'push_private',
        'digest_frequency',
        'theme',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_notifications' => 'boolean',
            'notification_mode' => NotificationMode::class,
            'push_notifications' => 'boolean',
            'push_private' => 'boolean',
        ];
    }

    /**
     * The effective notification mode, defaulting when nothing is stored.
     */
    public function notificationMode(): NotificationMode
    {
        return $this->notification_mode
            ?? NotificationMode::from((string) config('notifications.default_mode', 'digest'));
    }

    public function wantsEmailNotifications(): bool
    {
        return $this->email_notifications !== false
            && (bool) config('notifications.enabled', true);
    }

    public function wantsPushNotifications(): bool
    {
        return $this->push_notifications !== false
            && (bool) config('webpush.enabled', true);
    }

    /**
     * Whether push payloads must hide the headline and summary. Falls back to
     * the global privacy default when the user has no stored preference.
     */
    public function prefersPrivatePush(): bool
    {
        return $this->push_private ?? (bool) config('webpush.privacy_mode', true);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
