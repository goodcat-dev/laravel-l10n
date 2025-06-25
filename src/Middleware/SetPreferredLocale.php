<?php

namespace Goodcat\I10n\Middleware;

use Closure;
use Goodcat\I10n\I10n;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetPreferredLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        $locale ??= $request->user()?->preferred_locale;

        if (config('app.detect_browser_locale', false)) {
            $locale ??= I10n::detectBrowserLocale($request);
        }

        if ($locale && !App::isLocale($locale)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
