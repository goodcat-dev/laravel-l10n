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
        $locale = $request->session()->get('locale');

        $locale ??= $request->user()?->preferred_locale;

        $locale ??= $this->detectBrowserLocale($request);

        if ($locale && ! App::isLocale($locale)) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    protected function detectBrowserLocale(Request $request): ?string
    {
        $locales = array_intersect($request->getLanguages(), config('app.locales'));

        return array_pop($locales);
    }
}
