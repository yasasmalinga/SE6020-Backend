<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceDomainGuard
{
    /**
     * Restrict routes served by each domain deployment.
     *
     * SERVICE_DOMAIN values:
     * - users
     * - scheduling
     * - interaction
     * - payments
     * - realtime
     * - all (or unset for monolith/local)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $domain = (string) config('app.service_domain', env('SERVICE_DOMAIN', 'all'));
        if ($domain === '' || $domain === 'all') {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        if ($this->isAllowed($domain, $path)) {
            return $next($request);
        }

        return response()->json([
            'message' => sprintf('Route is not served by %s-service.', $domain),
            'service_domain' => $domain,
            'path' => $path,
        ], Response::HTTP_NOT_FOUND);
    }

    private function isAllowed(string $domain, string $path): bool
    {
        $allowedPrefixes = match ($domain) {
            'users' => [
                'api/health',
                'api/auth',
                'api/profiles',
                'api/interviewers',
                'api/users',
            ],
            'scheduling' => [
                'api/health',
                'api/bookings',
                'api/availability',
                'api/interviews',
                'api/scheduling',
            ],
            'interaction' => [
                'api/health',
                'api/submissions',
                'api/conversations',
                'api/messages',
                'api/interaction',
            ],
            'payments' => [
                'api/health',
                'api/payments',
                'api/interaction/payments',
            ],
            'realtime' => [
                'api/health',
                'api/realtime',
                'api/interaction/realtime',
            ],
            default => ['api/'],
        };

        foreach ($allowedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
