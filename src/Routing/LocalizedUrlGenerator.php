<?php

namespace Goodcat\L10n\Routing;

use BackedEnum;
use Goodcat\L10n\Contracts\HasLocalizedRouteKey;
use Goodcat\L10n\Contracts\LocalizedRoute;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class LocalizedUrlGenerator extends UrlGenerator
{
    public function route($name, $parameters = [], $absolute = true): string
    {
        if ($name instanceof BackedEnum) {
            if (! is_string($name = $name->value)) {
                throw new InvalidArgumentException('Attribute [name] expects a string backed enum.');
            }
        }

        $parameters = Arr::wrap($parameters);

        $locale = Arr::pull($parameters, 'lang');

        if (! is_null($route = $this->routes->getByName($name))) {
            /** @var Route&LocalizedRoute $route */
            $locale ??= $route->getAction('canonical') ? $route->locale() : app()->getLocale();

            return $this->toLocalizedRoute($route, $locale, $parameters, $absolute);
        }

        return parent::route($name, $parameters, $absolute);
    }

    /**
     * @param  string|array<int|string, mixed>  $action
     */
    public function action($action, $parameters = [], $absolute = true): string
    {
        if (is_null($route = $this->routes->getByAction($action = $this->formatAction($action)))) {
            throw new InvalidArgumentException("Action {$action} not defined.");
        }

        $parameters = Arr::wrap($parameters);

        $locale = Arr::pull($parameters, 'lang')
            ?? ($route->getAction('canonical') ? $route->locale() : app()->getLocale());

        /** @var Route&LocalizedRoute $route */
        return $this->toLocalizedRoute($route, $locale, $parameters, $absolute);
    }

    /**
     * @param  Route&LocalizedRoute  $route
     * @param  array<int|string, mixed>  $parameters
     *
     * @throws UrlGenerationException
     */
    protected function toLocalizedRoute(Route $route, string $locale, array $parameters, bool $absolute): string
    {
        /** @var Route&LocalizedRoute $route */
        $route = $route->getTranslations($locale)[$locale] ?? $route;

        if (is_null($this->localizeParameters($route, $parameters))) {
            $route = $route->canonical();
        }

        return $this->toRoute($route, $parameters, $absolute);
    }

    public function toRoute($route, $parameters, $absolute): string
    {
        $parameters = Arr::wrap($parameters);

        $keys = $this->localizedRouteKeys($route, $parameters);

        if ($missing = array_keys($keys, null, true)) {
            throw UrlGenerationException::forMissingParameters($route, array_map(
                fn (int|string $name) => is_int($name) ? $route->parameterNames()[$name] ?? $name : $name,
                $missing,
            ));
        }

        return parent::toRoute($route, array_replace($parameters, $keys), $absolute);
    }

    /**
     * @param  array<int|string, mixed>  $parameters
     * @return array<int|string, mixed>|null
     */
    public function localizeParameters(Route $route, array $parameters): ?array
    {
        $keys = $this->localizedRouteKeys($route, $parameters);

        return in_array(null, $keys, true) ? null : array_replace($parameters, $keys);
    }

    /**
     * @param  array<int|string, mixed>  $parameters
     * @return array<int|string, ?string>
     */
    protected function localizedRouteKeys(Route $route, array $parameters): array
    {
        $keys = [];

        /** @var Route&LocalizedRoute $route */
        if (! $route->getAction('lang') && ! $route->getAction('locale')) {
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
