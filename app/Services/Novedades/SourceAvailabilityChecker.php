<?php

namespace App\Services\Novedades;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SourceAvailabilityChecker
{
    /**
     * Perform a lightweight request to verify a source is reachable. A failure
     * is never fatal: it only flags the source as unavailable.
     *
     * @return array{available: bool|null, checked_at: Carbon|null}
     */
    public function check(string $url): array
    {
        if (! (bool) config('novedades.source_check.enabled', true)) {
            return ['available' => null, 'checked_at' => null];
        }

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return ['available' => false, 'checked_at' => now()];
        }

        $method = strtoupper((string) config('novedades.source_check.method', 'HEAD'));
        $timeout = (int) config('novedades.source_check.timeout', 5);

        try {
            $response = Http::timeout($timeout)
                ->withOptions(['allow_redirects' => ['max' => 3]])
                ->send($method, $url);

            if ($method === 'HEAD' && in_array($response->status(), [403, 405, 501], true)) {
                $response = Http::timeout($timeout)->get($url);
            }

            return [
                'available' => $response->status() < 400,
                'checked_at' => now(),
            ];
        } catch (\Throwable $exception) {
            Log::info('Source availability check failed', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return ['available' => false, 'checked_at' => now()];
        }
    }
}
