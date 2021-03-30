<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

/**
 * Class AuthSimple
 * @package App\Http\Middleware
 */

class AuthSimple
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next)
    {
        $authRequest = $request->header('Authorization');

        if ($authRequest && config('auth.secret_token') === $authRequest) {
            return $next($request);
        }

        throw new AuthenticationException('Unauthenticated.');
    }
}
