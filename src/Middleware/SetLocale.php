<?php

namespace Goodcat\L10n\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route()->parameter('lang') ?? App::getFallbackLocale();

        App::setLocale($locale);

        return $next($request);
    }
}
