<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\CognitoJwtVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CognitoAuthenticate
{
    public function __construct(
        private readonly CognitoJwtVerifier $jwtVerifier
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($this->tokenLooksLikeJwt($token)) {
            if (! $this->jwtVerifier->isConfigured()) {
                return response()->json([
                    'message' => 'Cognito JWT auth is not configured. Set AWS_COGNITO_REGION, AWS_COGNITO_USER_POOL_ID, and AWS_COGNITO_APP_CLIENT_ID in backend .env',
                ], 401);
            }

            try {
                $claims = $this->jwtVerifier->verify($token);
            } catch (\Throwable $exception) {
                $body = ['message' => 'Invalid token.'];
                if (config('app.debug')) {
                    $body['error'] = $exception->getMessage();
                }

                return response()->json($body, 401);
            }

            $cognitoId = (string) ($claims->sub ?? '');
            if ($cognitoId === '') {
                return response()->json(['message' => 'Invalid token claims.'], 401);
            }

            $email = $this->resolveEmail($claims, $cognitoId);
            $name = (string) ($claims->name ?? $claims->username ?? $claims->{'cognito:username'} ?? $email ?? 'Cognito User');

            // Access tokens usually omit custom attributes; only sync role from JWT when the claim exists.
            $profileFromJwt = $this->profileTypeFromClaims($claims);

            $user = User::query()->firstOrCreate(
                ['cognito_id' => $cognitoId],
                [
                    'email' => $email,
                    'name' => $name,
                    'password' => bcrypt(str()->random(40)),
                    'profile_type' => $profileFromJwt ?? 'candidate',
                ]
            );

            $dirty = false;
            if ($email && $user->email !== $email) {
                $user->email = $email;
                $dirty = true;
            }
            if ($name && $user->name !== $name) {
                $user->name = $name;
                $dirty = true;
            }
            if ($profileFromJwt !== null && $user->profile_type !== $profileFromJwt) {
                $user->profile_type = $profileFromJwt;
                $dirty = true;
            }
            if ($dirty) {
                $user->save();
            }

            if ($user->isInterviewer()) {
                $user->interviewer()->firstOrCreate([], []);
            }
            if ($user->isCandidate()) {
                $user->candidate()->firstOrCreate([], []);
            }

            $request->setUserResolver(fn () => $user);

            return $next($request);
        }

        $user = User::query()
            ->where('api_token', hash('sha256', $token))
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        if ($user->isInterviewer()) {
            $user->interviewer()->firstOrCreate([], []);
        }
        if ($user->isCandidate()) {
            $user->candidate()->firstOrCreate([], []);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function tokenLooksLikeJwt(string $token): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/', $token);
    }

    /**
     * @return 'candidate'|'interviewer'|null null when the token carries no role claim (typical for access tokens).
     */
    private function profileTypeFromClaims(object $claims): ?string
    {
        if (! isset($claims->{'custom:profile_type'})) {
            return null;
        }

        $raw = (string) $claims->{'custom:profile_type'};

        return in_array($raw, ['candidate', 'interviewer'], true) ? $raw : 'candidate';
    }

    private function resolveEmail(object $claims, string $cognitoId): string
    {
        if (isset($claims->email) && is_string($claims->email) && $claims->email !== '') {
            return strtolower($claims->email);
        }

        $username = (string) ($claims->username ?? $claims->{'cognito:username'} ?? '');
        if ($username !== '' && str_contains($username, '@')) {
            return strtolower($username);
        }

        return strtolower($cognitoId).'@cognito.local';
    }
}
