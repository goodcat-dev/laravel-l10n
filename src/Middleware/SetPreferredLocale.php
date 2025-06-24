<?php

namespace Goodcat\I10n\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetPreferredLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->session()->put('locale', 'it');

        $locale = $request->session()->get('locale');

        $locale ??= $request->user()?->preferred_locale;

        if ($locale && ! App::isLocale($locale)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
