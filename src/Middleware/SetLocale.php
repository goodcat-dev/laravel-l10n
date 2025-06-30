<?php

namespace Goodcat\L10n\Middleware;

use Closure;
use Goodcat\L10n\L10n;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        $locale ??= $request->user()?->preferred_locale;

        if (config('app.detect_browser_locale', false)) {
            $locale ??= L10n::detectBrowserLocale($request);
        }

        if ($locale && !App::isLocale($locale)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
