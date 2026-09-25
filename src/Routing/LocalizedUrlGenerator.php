<?php

namespace Goodcat\L10n\Routing;

use BackedEnum;
use Goodcat\L10n\Contracts\LocalizedRoute;
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

            $route = $route->getTranslations($locale)[$locale] ?? $route;

            return $this->toRoute($route, $parameters, $absolute);
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
        $route = $route->getTranslations($locale)[$locale] ?? $route;

        return $this->toRoute($route, $parameters, $absolute);
    }
}
