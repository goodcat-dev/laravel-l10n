<?php

namespace Goodcat\I10n\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RedirectToPreferredLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        $routeLocale = $route->getAction('locale') ?: App::getFallbackLocale();

        $redirect = str_replace(".$routeLocale", '', "{$route->getName()}." . App::getLocale());

        if (Route::has($redirect) && !App::isLocale($routeLocale)) {
            return redirect()->route($redirect, $route->parameters());
        }

        return $next($request);
    }
}
