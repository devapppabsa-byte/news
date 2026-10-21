<?php

namespace App\Support;

/**
 * The whitelist of runtime-configurable settings, their type, and the config
 * key used as the fallback when no database value is stored. Only keys listed
 * here may be read from or written to the settings table.
 */
class SettingKeys
{
    public const CUSTOM_INTERVAL_MIN = 'monitoring.custom_interval.min_minutes';

    public const CUSTOM_INTERVAL_MAX = 'monitoring.custom_interval.max_minutes';

    public const FREQUENCIES = 'monitoring.frequencies';

    public const MAX_TOPICS_PER_USER = 'topics.max_per_user';

    public const SEARCH_MAX_RESULTS = 'search.max_results';

    public const SEARCH_TIMEOUT = 'search.timeout';

    public const SEARCH_RETRIES = 'search.retries';

    public const CONCURRENCY_MAX_GLOBAL = 'monitoring.concurrency.max_global';

    public const CONCURRENCY_MAX_PER_USER = 'monitoring.concurrency.max_per_user';

    public const NOTIFICATIONS_MAX_PER_DAY = 'notifications.limits.max_per_day';

    public const DIGEST_AT = 'notifications.digest.at';

    public const DIGEST_MAX_ITEMS = 'notifications.digest.max_items';

    public const COST_PER_RESULT = 'monitoring.costs.per_result';

    public const COST_PER_1K_TOKENS = 'monitoring.costs.per_1k_tokens_ai';

    public const RETENTION_TOPIC_RUNS_DAYS = 'retention.topic_runs_days';

    public const RETENTION_DELIVERIES_DAYS = 'retention.notification_deliveries_days';

    public const RETENTION_NEWS_DAYS = 'retention.news_entries_days';

    public const METRICS_WINDOW_DAYS = 'monitoring.metrics.window_days';

    public const MAIL_FROM_ADDRESS = 'mail.from_address';

    public const MAIL_FROM_NAME = 'mail.from_name';

    public const NOTIFICATIONS_ENABLED = 'notifications.enabled';

    public const NOTIFICATIONS_MIN_RELEVANCE = 'notifications.min_relevance';

    /**
     * Settings rendered on the dedicated email page rather than the global one.
     *
     * @return array<int, string>
     */
    public static function mailKeys(): array
    {
        return [
            self::MAIL_FROM_ADDRESS,
            self::MAIL_FROM_NAME,
            self::NOTIFICATIONS_ENABLED,
            self::NOTIFICATIONS_MIN_RELEVANCE,
        ];
    }

    /**
     * The settings shown on the global settings page.
     *
     * @return array<int, string>
     */
    public static function globalKeys(): array
    {
        return array_values(array_diff(self::all(), self::mailKeys()));
    }

    /**
     * Fallback configuration key for each setting. When a config key maps to a
     * nested config value, we can resolve it via dot notation.
     *
     * @return array<string, string>
     */
    public static function configMap(): array
    {
        return [
            self::CUSTOM_INTERVAL_MIN => 'monitoring.custom_interval.min_minutes',
            self::CUSTOM_INTERVAL_MAX => 'monitoring.custom_interval.max_minutes',
            self::SEARCH_MAX_RESULTS => 'search.max_results',
            self::SEARCH_TIMEOUT => 'search.timeout',
            self::SEARCH_RETRIES => 'search.retries',
            self::CONCURRENCY_MAX_GLOBAL => 'monitoring.concurrency.max_global',
            self::CONCURRENCY_MAX_PER_USER => 'monitoring.concurrency.max_per_user',
            self::NOTIFICATIONS_MAX_PER_DAY => 'notifications.limits.max_per_day',
            self::DIGEST_AT => 'notifications.digest.at',
            self::DIGEST_MAX_ITEMS => 'notifications.digest.max_items',
            self::COST_PER_RESULT => 'monitoring.costs.per_result',
            self::COST_PER_1K_TOKENS => 'monitoring.costs.per_1k_tokens_ai',
            self::METRICS_WINDOW_DAYS => 'monitoring.metrics.window_days',
            self::MAIL_FROM_ADDRESS => 'mail.from.address',
            self::MAIL_FROM_NAME => 'mail.from.name',
            self::NOTIFICATIONS_ENABLED => 'notifications.enabled',
            self::NOTIFICATIONS_MIN_RELEVANCE => 'notifications.min_relevance',
        ];
    }

    /**
     * The literal default for keys that do not resolve to an existing config
     * value.
     *
     * @return array<string, int|float|string|array<int, string>>
     */
    public static function defaults(): array
    {
        return [
            self::FREQUENCIES => ['hourly', 'daily', 'weekly', 'manual', 'custom'],
            self::MAX_TOPICS_PER_USER => 0,
            self::RETENTION_TOPIC_RUNS_DAYS => 90,
            self::RETENTION_DELIVERIES_DAYS => 90,
            self::RETENTION_NEWS_DAYS => 180,
            self::METRICS_WINDOW_DAYS => 30,
        ];
    }

    /**
     * The value type for each key.
     *
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::CUSTOM_INTERVAL_MIN => 'int',
            self::CUSTOM_INTERVAL_MAX => 'int',
            self::FREQUENCIES => 'array',
            self::MAX_TOPICS_PER_USER => 'int',
            self::SEARCH_MAX_RESULTS => 'int',
            self::SEARCH_TIMEOUT => 'int',
            self::SEARCH_RETRIES => 'int',
            self::CONCURRENCY_MAX_GLOBAL => 'int',
            self::CONCURRENCY_MAX_PER_USER => 'int',
            self::NOTIFICATIONS_MAX_PER_DAY => 'int',
            self::DIGEST_AT => 'int',
            self::DIGEST_MAX_ITEMS => 'int',
            self::COST_PER_RESULT => 'float',
            self::COST_PER_1K_TOKENS => 'float',
            self::RETENTION_TOPIC_RUNS_DAYS => 'int',
            self::RETENTION_DELIVERIES_DAYS => 'int',
            self::RETENTION_NEWS_DAYS => 'int',
            self::METRICS_WINDOW_DAYS => 'int',
            self::MAIL_FROM_ADDRESS => 'string',
            self::MAIL_FROM_NAME => 'string',
            self::NOTIFICATIONS_ENABLED => 'bool',
            self::NOTIFICATIONS_MIN_RELEVANCE => 'int',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::types());
    }

    public static function isKnown(string $key): bool
    {
        return array_key_exists($key, self::types());
    }

    /**
     * Validation rules for the admin settings form, keyed by setting key.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            self::CUSTOM_INTERVAL_MIN => ['required', 'integer', 'min:1', 'max:43200'],
            self::CUSTOM_INTERVAL_MAX => ['required', 'integer', 'min:1', 'max:43200'],
            self::FREQUENCIES => ['required', 'array', 'min:1'],
            self::FREQUENCIES.'.*' => ['string', 'in:'.implode(',', self::defaults()[self::FREQUENCIES])],
            self::MAX_TOPICS_PER_USER => ['required', 'integer', 'min:0', 'max:1000'],
            self::SEARCH_MAX_RESULTS => ['required', 'integer', 'min:1', 'max:100'],
            self::SEARCH_TIMEOUT => ['required', 'integer', 'min:1', 'max:120'],
            self::SEARCH_RETRIES => ['required', 'integer', 'min:0', 'max:10'],
            self::CONCURRENCY_MAX_GLOBAL => ['required', 'integer', 'min:1', 'max:100'],
            self::CONCURRENCY_MAX_PER_USER => ['required', 'integer', 'min:1', 'max:100'],
            self::NOTIFICATIONS_MAX_PER_DAY => ['required', 'integer', 'min:0', 'max:10000'],
            self::DIGEST_AT => ['required', 'integer', 'min:0', 'max:23'],
            self::DIGEST_MAX_ITEMS => ['required', 'integer', 'min:1', 'max:1000'],
            self::COST_PER_RESULT => ['required', 'numeric', 'min:0', 'max:1000'],
            self::COST_PER_1K_TOKENS => ['required', 'numeric', 'min:0', 'max:10000'],
            self::RETENTION_TOPIC_RUNS_DAYS => ['required', 'integer', 'min:0', 'max:3650'],
            self::RETENTION_DELIVERIES_DAYS => ['required', 'integer', 'min:0', 'max:3650'],
            self::RETENTION_NEWS_DAYS => ['required', 'integer', 'min:0', 'max:3650'],
            self::METRICS_WINDOW_DAYS => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * Validation rules for the dedicated email settings page.
     *
     * @return array<string, array<int, string>>
     */
    public static function mailRules(): array
    {
        return [
            self::MAIL_FROM_ADDRESS => ['required', 'email', 'max:255'],
            self::MAIL_FROM_NAME => ['nullable', 'string', 'max:255'],
            self::NOTIFICATIONS_ENABLED => ['required', 'boolean'],
            self::NOTIFICATIONS_MIN_RELEVANCE => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
