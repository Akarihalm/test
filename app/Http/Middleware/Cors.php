<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Class Cors
 * @package App\Http\Middleware
 */

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($permittedUrl = $this->allowedDomain($request)) {
            return $next($request)
                ->header('Access-Control-Allow-Origin', $permittedUrl)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Origin, Authorization, Accept, X-Requested-With, Content-Type');
        }

        return $next($request);
    }

    /**
     * @param Request $request
     * @return false|string
     */
    private function allowedDomain(Request $request)
    {
        $currentHost = rtrim($this->getReferer($request), '/');

        foreach (config('app.source_urls') as $url) {
            $format = rtrim($url, '/');
            if ($format === $currentHost) {
                return $format;
            }
        }

        return false;
    }

    /**
     * @param Request $request
     * @return array|string|null
     */
    private function getReferer(Request $request)
    {
        return $request->server('HTTP_ORIGIN');
    }
}
