<?php

namespace Goodcat\L10n\Routing;

use Goodcat\L10n\Contracts\HasLocalizedRouteKey;
use Goodcat\L10n\Contracts\LocalizedRoute;
use Goodcat\L10n\Routing\Exceptions\MissingLocalizedRouteKeyException;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Arr;

class LocalizedUrlGenerator extends UrlGenerator
{
    /**
     * @param  Route&LocalizedRoute  $route
     *
     * @throws UrlGenerationException
     */
    public function toRoute($route, $parameters, $absolute): string
    {
        if (! $route->isCanonical() && ! $route->isTranslation()) {
            return parent::toRoute($route, $parameters, $absolute);
        }

        $parameters = Arr::wrap($parameters);

        $lang = Arr::pull($parameters, 'lang');

        // Fallback is allowed only when the locale is resolved from the app,
        // not explicitly requested by the developer. E.g. route('post', ['lang' => 'es'])
        $canFallback = $lang === null && ! $route->isTranslation();

        $locale = $lang ?? ($route->isTranslation() ? $route->locale() : app()->getLocale());

        $translation = $route->getTranslation($locale) ?? $route;

        $candidates = $canFallback && $translation !== $route->canonical()
            ? [$translation, $route->canonical()]
            : [$translation];

        foreach ($candidates as $candidate) {
            $keys = $this->localizedRouteKeys($candidate, $parameters);

            if (! in_array(null, $keys, true)) {
                return parent::toRoute($candidate, array_replace($parameters, $keys), $absolute);
            }
        }

        throw MissingLocalizedRouteKeyException::forParameters($candidate, array_keys($keys, null, true));
    }

    /**
     * @internal
     *
     * @param  Route&LocalizedRoute  $route
     * @param  array<int|string, mixed>  $parameters
     * @return array<string, string>
     *
     * @throws UrlGenerationException
     */
    public function toTranslations(Route $route, array $parameters = [], bool $absolute = true): array
    {
        $urls = [];

        foreach ($route->getTranslations() as $locale => $translation) {
            try {
                $urls[$locale] = $this->toRoute($translation, ['lang' => $locale] + $parameters, $absolute);
            } catch (MissingLocalizedRouteKeyException) {
            }
        }

        return $urls;
    }

    /**
     * @param  array<int|string, mixed>  $parameters
     * @return array<int|string, ?string>
     */
    protected function localizedRouteKeys(Route $route, array $parameters): array
    {
        $keys = [];

        foreach ($parameters as $name => $parameter) {
            if ($parameter instanceof HasLocalizedRouteKey) {
                $keys[$name] = $parameter->getLocalizedRouteKey($route->locale(), $route->bindingFieldFor($name));
            }
        }

        return $keys;
    }
}
