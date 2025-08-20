<?php

namespace Goodcat\L10n\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Matching\UriValidator;
use Illuminate\Routing\Route;

class LocalizedUriValidator extends UriValidator
{
    public function matches(Route $route, Request $request): bool
    {
        $matches = parent::matches($route, $request);

        $availableLocales = $route->getAction('lang');

        if ($matches || !$availableLocales) {
            return $matches;
        }

        $segments = count(explode('/', $route->uri));

        $path = rtrim($request->getPathInfo(), '/') ?: '/';

        if (count($request->segments()) <= --$segments) {
            $route->setUri(str_replace('{lang}/', '', $route->uri));

            $route->compiled = $route->toSymfonyRoute()->compile();

            $matches = preg_match($route->getCompiled()->getRegex(), rawurldecode($path));
        }

        $locale = $this->guessLocaleFromPath($route, $request->getPathInfo());

        if (!$matches && $locale) {
            $this->replaceUriWithTranslation($route, $locale);

            $matches = preg_match($route->getCompiled()->getRegex(), rawurldecode($path));
        }

        return $matches;
    }

    protected function replaceUriWithTranslation(Route $route, string $locale): void
    {
        $uri = $route->action['lang'][$locale];

        if (!$uri) {
            return;
        }

        $localized = new Route($route->methods, $uri, $route->action);

        $route->action['original_uri'] = $route->uri;

        $route->setUri($localized->uri);

        $route->compiled = $route->toSymfonyRoute()->compile();
    }

    protected function guessLocaleFromPath(Route $route, string $path): ?string
    {
        $locales = array_keys($route->getAction('lang'));

        $length = max(array_map('strlen', $locales));

        $portion = substr($path, strpos("/$route->uri", '/{lang}'), $length + 2);

        foreach ($locales as $locale) {
            if (str_contains($portion, "/$locale/")) {
                return $locale;
            }
        }

        return null;
    }
}
