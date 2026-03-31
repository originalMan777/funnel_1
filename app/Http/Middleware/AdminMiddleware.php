<?php

namespace App\Http\Middleware;

use App\Services\ContentFormula\ContentFormulaEventLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isGeneratorRoute = $request->routeIs('admin.content-formula.*');

        if (!$user) {
            if ($isGeneratorRoute) {
                app(ContentFormulaEventLogger::class)->warning('generator_access_denied_forbidden', $request, [
                    'action_type' => 'admin_middleware',
                ]);
            }

            abort(403);
        }

        if (!method_exists($user, 'canManageAdminPanel')) {
            if ($isGeneratorRoute) {
                app(ContentFormulaEventLogger::class)->warning('generator_access_denied_forbidden', $request, [
                    'action_type' => 'admin_middleware',
                ]);
            }

            abort(403);
        }

        if (!$user->canManageAdminPanel()) {
            if ($isGeneratorRoute) {
                app(ContentFormulaEventLogger::class)->warning('generator_access_denied_forbidden', $request, [
                    'action_type' => 'admin_middleware',
                ]);
            }

            abort(403);
        }

        return $next($request);
    }
}
