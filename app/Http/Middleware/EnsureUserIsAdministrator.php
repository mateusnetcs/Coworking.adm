<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isAdministrator()) {
            abort(Response::HTTP_FORBIDDEN, 'Administrator access required.');
        }

        return $next($request);
    }
}
