<?php

namespace Goodcat\L10n\Routing;

use Goodcat\L10n\Contracts\HasLocalizedRouteKey;
use Goodcat\L10n\Contracts\LocalizedRoute;
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

        $locale = Arr::pull($parameters, 'lang')
            ?? ($route->isTranslation() ? $route->locale() : app()->getLocale());

        $translation = $route->getTranslation($locale) ?? $route;

        $canonical = $route->canonical();

        foreach ([$translation, $canonical] as $candidate) {
            if (! is_null($url = $this->toExactRoute($candidate, $parameters, $absolute))) {
                return $url;
            }
        }

        $missing = array_keys($this->localizedRouteKeys($canonical, $parameters), null, true);

        throw UrlGenerationException::forMissingParameters($canonical, array_map(
            fn (int|string $name) => is_int($name) ? $canonical->parameterNames()[$name] ?? $name : $name,
            $missing,
        ));
    }

    /**
     * @param  array<int|string, mixed>  $parameters
     *
     * @throws UrlGenerationException
     */
    public function toExactRoute(Route $route, array $parameters, bool $absolute = true): ?string
    {
        $keys = $this->localizedRouteKeys($route, $parameters);

        return in_array(null, $keys, true) ? null : parent::toRoute($route, array_replace($parameters, $keys), $absolute);
    }

    /**
     * @param  array<int|string, mixed>  $parameters
     * @return array<int|string, ?string>
     */
    protected function localizedRouteKeys(Route $route, array $parameters): array
    {
        $keys = [];

        /** @var Route&LocalizedRoute $route */
        if (! $route->isCanonical() && ! $route->isTranslation()) {
            return $keys;
        }

        foreach ($parameters as $name => $parameter) {
            if ($parameter instanceof HasLocalizedRouteKey) {
                $keys[$name] = $parameter->getLocalizedRouteKey($route->locale(), $route->bindingFieldFor($name));
            }
        }

        return $keys;
    }
}
