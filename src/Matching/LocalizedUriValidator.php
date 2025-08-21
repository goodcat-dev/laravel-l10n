<?php

namespace Goodcat\L10n\Matching;

use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Http\Request;
use Illuminate\Routing\Matching\UriValidator;
use Illuminate\Routing\Route;

class LocalizedUriValidator extends UriValidator
{
    public function matches(Route $route, Request $request): bool
    {
        $matches = parent::matches($route, $request);

        /** @var RouteTranslations $translations */
        $translations = $route->lang();

        if ($matches || $translations->isEmpty()) {
            return $matches;
        }

        $segments = count(explode('/', $route->uri));

        $path = rtrim($request->getPathInfo(), '/') ?: '/';

        if (count($request->segments()) <= --$segments) {
            $missing = new Route(
                $route->methods(str_replace('{lang}/', '', $route->uri)),
                str_replace('{lang}/', '', $route->uri),
                $route->action
            );

            $missing->compiled = $missing->toSymfonyRoute()->compile();

            $matches = preg_match($missing->getCompiled()->getRegex(), rawurldecode($path));
        }

        $locale = $translations->guessLocaleFromPath($route->uri, $request->getPathInfo());

        if (!$matches && $locale) {
            $localized = new Route($route->methods, $translations->get($locale), $route->action);

            $localized->compiled = $localized->toSymfonyRoute()->compile();

            $matches = preg_match($localized->getCompiled()->getRegex(), rawurldecode($path));
        }

        return $matches;
    }
}