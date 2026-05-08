<?php

$splitEnvList = static fn (string $value): array => array_values(array_filter(array_map('trim', explode(',', $value))));

$frontendUrls = $splitEnvList((string) env(
    'FRONTEND_URL',
    env('APP_ENV', 'production') === 'local' ? '*' : ''
));

$frontendUrlPatterns = array_map(
    static function (string $pattern): string {
        if ($pattern === '') {
            return '';
        }

        $delimiter = $pattern[0];
        if (in_array($delimiter, ['#', '~', '/', '%'], true) && strrpos($pattern, $delimiter) > 0) {
            return $pattern;
        }

        return '~'.$pattern.'~';
    },
    $splitEnvList((string) env('FRONTEND_URL_PATTERN', ''))
);

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Comma-separated exact origins in env, e.g.:
    // FRONTEND_URL=https://main.ds3ab0fmqpx1t.amplifyapp.com,https://app.example.com
    'allowed_origins' => $frontendUrls,

    // Comma-separated regex origins in env, e.g.:
    // FRONTEND_URL_PATTERN=^https://[a-z0-9.-]+\.amplifyapp\.com$
    'allowed_origins_patterns' => array_values(array_filter($frontendUrlPatterns)),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Bearer-token auth is used; cookies are not required cross-origin.
    'supports_credentials' => false,
];
