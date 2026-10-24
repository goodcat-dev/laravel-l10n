<?php

namespace Goodcat\L10n\Middleware;

use Closure;
use Goodcat\L10n\Contracts\LocalizedRoute;
use Goodcat\L10n\Routing\LocalizedUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

class RedirectToPreferredLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     *
     * @throws UrlGenerationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')
            || $request->session()->get('l10n.redirected_to_preferred_locale')
        ) {
            return $next($request);
        }

        /** @var (Route&LocalizedRoute)|null $route */
        $route = $request->route();

        if (! $route) {
            return $next($request);
        }

        $urls = app(LocalizedUrlGenerator::class)->toTranslations($route, $route->parameters() + $request->query());

        if (count($urls) < 2) {
            return $next($request);
        }

        $request->session()->put('l10n.redirected_to_preferred_locale', true);

        $locale = app()->getPreferredLocale(array_keys($urls));

        if (! $locale || $locale === $route->locale()) {
            return $next($request);
        }

        return redirect()->to($urls[$locale]);
    }
}
