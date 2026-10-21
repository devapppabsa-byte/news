<?php

namespace App\Services\Settings;

use App\Models\Setting;
use App\Support\SettingKeys;
use InvalidArgumentException;

class SettingsRepository
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $cache = null;

    /**
     * Resolve a setting value, preferring the database over the config/default
     * fallback. The value is cast according to its declared type.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $raw = $this->all()[$key] ?? null;

        if ($raw === null) {
            return $default ?? $this->fallback($key);
        }

        return $this->cast($key, $raw);
    }

    public function integer(string $key, ?int $default = null): int
    {
        return (int) $this->get($key, $default);
    }

    public function float(string $key, ?float $default = null): float
    {
        return (float) $this->get($key, $default);
    }

    public function boolean(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array<int, string>
     */
    public function array(string $key): array
    {
        $value = $this->get($key);

        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return [];
    }

    /**
     * Persist a value, storing it in its canonical string representation.
     */
    public function set(string $key, mixed $value): Setting
    {
        if (! SettingKeys::isKnown($key)) {
            throw new InvalidArgumentException("Unknown setting key [{$key}].");
        }

        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $this->serialize($value), 'type' => SettingKeys::types()[$key]],
        );

        $this->cache = null;

        return $setting;
    }

    public function forget(string $key): void
    {
        if (! SettingKeys::isKnown($key)) {
            throw new InvalidArgumentException("Unknown setting key [{$key}].");
        }

        Setting::query()->where('key', $key)->delete();

        $this->cache = null;
    }

    public function flush(): void
    {
        $this->cache = null;
    }

    /**
     * Remove every stored value so all settings fall back to their config or
     * literal default.
     */
    public function forgetAll(): void
    {
        Setting::query()->whereIn('key', SettingKeys::all())->delete();

        $this->cache = null;
    }

    /**
     * @return array<string, string>
     */
    protected function all(): array
    {
        return $this->cache ??= Setting::query()
            ->get()
            ->mapWithKeys(fn (Setting $setting): array => [$setting->key => (string) $setting->value])
            ->all();
    }

    protected function fallback(string $key): mixed
    {
        $configKey = SettingKeys::configMap()[$key] ?? null;

        if ($configKey !== null && config()->has($configKey)) {
            return config($configKey);
        }

        return SettingKeys::defaults()[$key] ?? null;
    }

    protected function cast(string $key, string $value): mixed
    {
        return match (SettingKeys::types()[$key] ?? 'string') {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => json_decode($value, true) ?: [],
            default => $value,
        };
    }

    protected function serialize(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
