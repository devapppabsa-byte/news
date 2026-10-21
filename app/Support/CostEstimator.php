<?php

namespace App\Support;

use App\Services\Settings\SettingsRepository;

class CostEstimator
{
    public function __construct(
        protected ?SettingsRepository $settings = null,
    ) {}

    /**
     * Estimate the cost of a run from its raw usage. These figures are rough
     * and based on configurable rates; they are not real provider billing.
     *
     * @param  array{results?: int, ai_tokens?: int}  $usage
     */
    public function estimate(array $usage): float
    {
        $settings = $this->settings ?? app(SettingsRepository::class);

        $perResult = $settings->float(SettingKeys::COST_PER_RESULT);
        $per1kTokens = $settings->float(SettingKeys::COST_PER_1K_TOKENS);

        $results = max(0, (int) ($usage['results'] ?? 0));
        $tokens = max(0, (int) ($usage['ai_tokens'] ?? 0));

        return round(($results * $perResult) + (($tokens / 1000) * $per1kTokens), 6);
    }
}
