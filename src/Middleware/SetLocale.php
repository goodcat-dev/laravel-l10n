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

        /** @var ?string $locale */
        $locale = $route->parameter('lang', $route->getAction('locale'));

        if ($locale) {
            app()->setLocale($locale);

            $request->setLocale($locale);
        }

        $route->forgetParameter('lang');

        return $next($request);
    }
}
