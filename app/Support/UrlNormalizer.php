<?php

namespace App\Support;

class UrlNormalizer
{
    /**
     * Query parameters that only track the visitor and can be safely removed.
     *
     * @var array<int, string>
     */
    protected const TRACKING_PARAMS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id',
        'fbclid', 'gclid', 'dclid', 'msclkid', 'yclid', 'twclid', 'igshid',
        'mc_cid', 'mc_eid', 'mkt_tok', 'ref', 'ref_src', 'referrer', 'source',
        '_hsenc', '_hsmi', 'vero_id', 'oly_anon_id', 'oly_enc_id',
    ];

    /**
     * Normalize a URL into a canonical, comparable form.
     */
    public function normalize(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return strtolower($url);
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host']);
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $path = $this->normalizePath($parts['path'] ?? '/');
        $query = $this->normalizeQuery($parts['query'] ?? '');

        $normalized = $scheme.'://'.$host.$path;

        if ($query !== '') {
            $normalized .= '?'.$query;
        }

        return $normalized;
    }

    /**
     * A stable hash for the normalized URL.
     */
    public function hash(string $url): string
    {
        return hash('sha256', $this->normalize($url));
    }

    protected function normalizePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    protected function normalizeQuery(string $query): string
    {
        if ($query === '') {
            return '';
        }

        parse_str($query, $params);

        $filtered = array_filter(
            $params,
            fn (string $key): bool => ! in_array(strtolower($key), self::TRACKING_PARAMS, true),
            ARRAY_FILTER_USE_KEY,
        );

        ksort($filtered);

        return http_build_query($filtered);
    }
}
