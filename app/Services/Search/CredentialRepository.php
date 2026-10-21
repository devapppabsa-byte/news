<?php

namespace App\Services\Search;

use App\Models\SearchCredential;
use App\Support\CredentialKeys;

class CredentialRepository
{
    /**
     * @var array<string, string>|null
     */
    protected ?array $cache = null;

    /**
     * Resolve a credential for a provider, preferring the database over
     * the environment/config fallback.
     */
    public function get(string $provider, string $name, ?string $fallback = null): ?string
    {
        $value = $this->all()["$provider:$name"] ?? null;

        if (filled($value)) {
            return $value;
        }

        return filled($fallback) ? $fallback : null;
    }

    /**
     * Store (or replace) an encrypted credential value.
     */
    public function put(string $provider, string $name, ?string $value): void
    {
        if (blank($value)) {
            SearchCredential::query()
                ->where('provider', $provider)
                ->where('name', $name)
                ->delete();

            $this->cache = null;

            return;
        }

        SearchCredential::query()->updateOrCreate(
            ['provider' => $provider, 'name' => $name],
            ['value' => $value, 'is_active' => true],
        );

        $this->cache = null;
    }

    /**
     * Whether a credential has been stored in the database.
     */
    public function has(string $provider, string $name): bool
    {
        return filled($this->all()["$provider:$name"] ?? null);
    }

    /**
     * Report where a credential comes from: the encrypted store, the
     * environment/config fallback, or nowhere.
     *
     * @return 'stored'|'environment'|'missing'
     */
    public function status(string $provider, string $name, ?string $fallback = null): string
    {
        if ($this->has($provider, $name)) {
            return 'stored';
        }

        return filled($fallback) ? 'environment' : 'missing';
    }

    /**
     * Explicitly remove a stored credential, revealing any environment
     * fallback again.
     */
    public function forget(string $provider, string $name): void
    {
        SearchCredential::query()
            ->where('provider', $provider)
            ->where('name', $name)
            ->delete();

        $this->cache = null;
    }

    /**
     * The status of every credential in a scope, keyed by provider and field.
     *
     * @return array<string, array<string, string>>
     */
    public function statusesFor(string $scope): array
    {
        $statuses = [];

        foreach (CredentialKeys::fields($scope) as $provider => $names) {
            foreach (array_keys($names) as $name) {
                $statuses[$provider][$name] = $this->status(
                    $provider,
                    $name,
                    config(CredentialKeys::configKey($provider, $name)),
                );
            }
        }

        return $statuses;
    }

    /**
     * @return array<string, string>
     */
    protected function all(): array
    {
        return $this->cache ??= SearchCredential::query()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn (SearchCredential $credential): array => [
                "{$credential->provider}:{$credential->name}" => $credential->value,
            ])
            ->all();
    }
}
