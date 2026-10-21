<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class RelevancePreferences implements Castable
{
    public function __construct(
        public int $minRelevance = 0,
        public string $sort = 'date',
        public int $notifyThreshold = 0,
    ) {
        //
    }

    /**
     * Default values used when no preferences have been stored.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return config('monitoring.relevance');
    }

    /**
     * @param  array<int, mixed>  $arguments
     * @return CastsAttributes<RelevancePreferences, RelevancePreferences>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            /**
             * @param  array<string, mixed>  $attributes
             */
            public function get(Model $model, string $key, mixed $value, array $attributes): RelevancePreferences
            {
                $defaults = RelevancePreferences::defaults();
                $data = $value === null ? [] : (json_decode($value, true) ?: []);

                return new RelevancePreferences(
                    minRelevance: (int) Arr::get($data, 'min_relevance', $defaults['min_relevance']),
                    sort: (string) Arr::get($data, 'sort', $defaults['sort']),
                    notifyThreshold: (int) Arr::get($data, 'notify_threshold', $defaults['notify_threshold']),
                );
            }

            /**
             * @param  array<string, mixed>  $attributes
             */
            public function set(Model $model, string $key, mixed $value, array $attributes): ?string
            {
                if ($value === null) {
                    return null;
                }

                if ($value instanceof RelevancePreferences) {
                    return json_encode([
                        'min_relevance' => $value->minRelevance,
                        'sort' => $value->sort,
                        'notify_threshold' => $value->notifyThreshold,
                    ]);
                }

                return json_encode(Arr::only((array) $value, [
                    'min_relevance',
                    'sort',
                    'notify_threshold',
                ]));
            }
        };
    }
}
