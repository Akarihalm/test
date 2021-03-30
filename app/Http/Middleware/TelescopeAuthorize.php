<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Class TelescopeAuthorize
 * @package App\Http\Middleware
 */

class TelescopeAuthorize
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed|void
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('telescope.enabled')) {
            abort(403);
        }

        if (app()->isLocal()) {
            return $next($request);
        }

        if (! app()->isProduction()) {
            $this->checkBasicAuth($request);
            return $next($request);
        }

        abort(403);
    }

    /**
     * @param Request $request
     * @return void
     */
    private function checkBasicAuth(Request $request)
    {
        $correct = config('telescope.basic_auth');
        $request = [
            'user' => $request->server('PHP_AUTH_USER'),
            'password' => $request->server('PHP_AUTH_PW'),
        ];

        if ($correct['password'] && 0 === count(array_diff_assoc($correct, $request))) {
            return;
        }

        header('WWW-Authenticate: Basic realm="Enter username and password."');
        header('HTTP/1.0 401 Unauthorized');
        die('Unauthorized');
    }
}
