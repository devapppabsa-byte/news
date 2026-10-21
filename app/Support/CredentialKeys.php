<?php

namespace App\Support;

/**
 * The credential fields exposed in the admin panel, grouped by scope. A scope
 * only limits which providers/fields the UI may read or remove; credentials
 * themselves are stored by provider and name in the shared encrypted table.
 */
class CredentialKeys
{
    public const SCOPE_SEARCH = 'search';

    public const SCOPE_AI = 'ai';

    /**
     * Provider => field => human readable label, per scope.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public static function scopes(): array
    {
        return [
            self::SCOPE_SEARCH => [
                'brave' => ['key' => 'Brave Search API key'],
                'tavily' => ['key' => 'Tavily API key'],
                'google_cse' => ['key' => 'Google CSE API key', 'cx' => 'Google CSE engine ID (cx)'],
                'openrouter' => ['key' => 'OpenRouter API key'],
            ],
            self::SCOPE_AI => [
                'openai' => ['key' => 'OpenAI API key'],
                'anthropic' => ['key' => 'Anthropic API key'],
                'openrouter' => ['key' => 'OpenRouter API key'],
            ],
        ];
    }

    /**
     * The fields for a single scope.
     *
     * @return array<string, array<string, string>>
     */
    public static function fields(string $scope): array
    {
        return self::scopes()[$scope] ?? [];
    }

    public static function isKnown(string $scope, string $provider, string $name): bool
    {
        return isset(self::fields($scope)[$provider][$name]);
    }

    public static function label(string $scope, string $provider, string $name): string
    {
        return self::fields($scope)[$provider][$name] ?? "{$provider}.{$name}";
    }

    public static function configKey(string $provider, string $name): string
    {
        return "services.{$provider}.{$name}";
    }

    public static function providerLabel(string $provider): string
    {
        return ucfirst(str_replace('_', ' ', $provider));
    }

    /**
     * The admin page that owns a scope, used after a successful removal.
     */
    public static function routeName(string $scope): string
    {
        return $scope === self::SCOPE_AI ? 'admin.ai.settings' : 'admin.search.settings';
    }

    public static function isShared(string $provider, string $name): bool
    {
        $appearances = 0;

        foreach (self::scopes() as $fields) {
            if (isset($fields[$provider][$name])) {
                $appearances++;
            }
        }

        return $appearances > 1;
    }
}
