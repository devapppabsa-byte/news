<?php

namespace App\Services\Audit;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record an administrative action. Metadata must never contain secrets;
     * sensitive keys are redacted defensively.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(string $action, ?Model $subject = null, array $metadata = [], ?User $actor = null): AdminAuditLog
    {
        return AdminAuditLog::query()->create([
            'actor_id' => $actor?->getKey() ?? Auth::id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $this->sanitize($metadata),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    protected function sanitize(array $metadata): array
    {
        $blocked = ['password', 'password_confirmation', 'token', 'secret', 'api_key', 'key', 'value'];

        return collect($metadata)
            ->map(function (mixed $value, string|int $key) use ($blocked): mixed {
                if (in_array(strtolower((string) $key), $blocked, true)) {
                    return '[redacted]';
                }

                return is_array($value) ? $this->sanitize($value) : $value;
            })
            ->all();
    }

    /**
     * The leaves of a validated settings payload, used to describe what was
     * changed without storing the values themselves.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    public function changedKeys(array $payload): array
    {
        return array_keys(Arr::dot($payload));
    }
}
