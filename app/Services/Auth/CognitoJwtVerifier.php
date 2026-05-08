<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use stdClass;
use Throwable;

class CognitoJwtVerifier
{
    public function isConfigured(): bool
    {
        $pool = (string) config('cognito.user_pool_id', '');
        $client = (string) config('cognito.app_client_id', '');

        return $pool !== '' && $client !== '';
    }

    /**
     * Verify a Cognito access or ID token and return decoded claims.
     *
     * @throws Throwable
     */
    public function verify(string $jwt): stdClass
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Cognito is not configured (missing pool id or app client id).');
        }

        $region = (string) config('cognito.region');
        $userPoolId = (string) config('cognito.user_pool_id');
        $appClientId = (string) config('cognito.app_client_id');

        $jwksUrl = sprintf(
            'https://cognito-idp.%s.amazonaws.com/%s/.well-known/jwks.json',
            $region,
            $userPoolId
        );

        $jwks = Cache::remember("cognito_jwks_{$userPoolId}", 3600, function () use ($jwksUrl, $userPoolId, $region) {
            try {
                $response = Http::timeout(15)->connectTimeout(10)->get($jwksUrl);
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    'Unable to reach Cognito JWKS URL (network/TLS). '.$e->getMessage(),
                    0,
                    $e
                );
            }

            if (! $response->successful()) {
                $detail = $response->body();
                $decoded = $response->json();
                if (is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])) {
                    $detail = $decoded['message'];
                }
                $detail = strlen($detail) > 400 ? substr($detail, 0, 400).'…' : $detail;

                throw new \RuntimeException(sprintf(
                    'Unable to fetch Cognito JWKS (HTTP %d). Pool: %s region: %s. %s',
                    $response->status(),
                    $userPoolId,
                    $region,
                    $detail !== '' ? $detail : 'Empty response body.'
                ));
            }

            $json = $response->json();
            if (! is_array($json) || ! isset($json['keys'])) {
                throw new \RuntimeException('Invalid Cognito JWKS response (missing keys array).');
            }

            return $json;
        });

        $keys = JWK::parseKeySet($jwks);
        $decoded = JWT::decode($jwt, $keys);

        $expectedIss = sprintf('https://cognito-idp.%s.amazonaws.com/%s', $region, $userPoolId);
        if (($decoded->iss ?? '') !== $expectedIss) {
            throw new \RuntimeException('Invalid token issuer.');
        }

        $tokenUse = (string) ($decoded->token_use ?? '');
        if ($tokenUse === 'access') {
            if (($decoded->client_id ?? '') !== $appClientId) {
                throw new \RuntimeException('Invalid token client.');
            }
        } elseif ($tokenUse === 'id') {
            if (($decoded->aud ?? '') !== $appClientId) {
                throw new \RuntimeException('Invalid token audience.');
            }
        } else {
            throw new \RuntimeException('Unsupported Cognito token_use.');
        }

        return $decoded;
    }
}
