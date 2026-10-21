<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSuspendedWrites
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isSuspended() && ! $request->isMethodSafe()) {
            abort(Response::HTTP_FORBIDDEN, 'Your account is suspended and is currently read-only.');
        }

        return $next($request);
    }
}
