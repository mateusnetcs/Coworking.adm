<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldUseHttps() && ! $request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', 'upgrade-insecure-requests');

        if ($request->secure() && $this->shouldUseHttps()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=63072000; includeSubDomains; preload'
            );
        }

        return $response;
    }

    private function shouldUseHttps(): bool
    {
        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOL)) {
            return true;
        }

        foreach (['SERVICE_URL_APP', 'APP_URL', 'FRONTEND_URL'] as $key) {
            $value = env($key);

            if (is_string($value) && str_starts_with($value, 'https://')) {
                return true;
            }
        }

        return false;
    }
}
