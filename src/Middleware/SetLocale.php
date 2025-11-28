<?php

namespace Goodcat\L10n\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if ($locale = $route->getAction('locale')) {
            app()->setLocale($locale);

            $request->setLocale($locale);
        }

        return $next($request);
    }
}
