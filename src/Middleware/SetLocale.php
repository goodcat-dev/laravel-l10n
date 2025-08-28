<?php

namespace Goodcat\L10n\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        $locale = $route->parameter('lang', $route->getAction('locale'));

        if ($locale) {
            \app()->setLocale($locale);
        }

        return $next($request);
    }
}