<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $csp = $this->buildContentSecurityPolicy($request);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), browsing-topics=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        if ($request->isSecure() || (bool) config('session.secure')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }

    private function buildContentSecurityPolicy(Request $request): string
    {
        $isLocal = app()->isLocal();

        $connectSources = [
            "'self'",
        ];

        $scriptSources = [
            "'self'",
        ];

        $styleSources = [
            "'self'",
            "'unsafe-inline'",
        ];

        if ($isLocal) {
            $connectSources[] = 'http://127.0.0.1:*';
            $connectSources[] = 'http://localhost:*';
            $connectSources[] = 'ws://127.0.0.1:*';
            $connectSources[] = 'ws://localhost:*';

            $scriptSources[] = "'unsafe-eval'";

            $styleSources[] = 'http://127.0.0.1:*';
            $styleSources[] = 'http://localhost:*';
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "form-action 'self'",
            "script-src " . implode(' ', array_unique($scriptSources)),
            "style-src " . implode(' ', array_unique($styleSources)),
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src " . implode(' ', array_unique($connectSources)),
            "media-src 'self' blob:",
            "frame-src 'none'",
            "worker-src 'self' blob:",
            "manifest-src 'self'",
            "upgrade-insecure-requests",
            "block-all-mixed-content",
        ];

        return implode('; ', $directives);
    }
}
