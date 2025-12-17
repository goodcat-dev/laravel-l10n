<?php

namespace Goodcat\L10n\Routing;

use BackedEnum;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class LocalizedUrlGenerator extends UrlGenerator
{
    public function route($name, $parameters = [], $absolute = true): string
    {
        if ($name instanceof BackedEnum && ! is_string($name = $name->value)) {
            throw new InvalidArgumentException('Attribute [name] expects a string backed enum.');
        }

        $locale = Arr::pull($parameters, 'lang', app()->getLocale());

        if (! is_null($route = $this->routes->getByName($name))) {
            $route = $route->makeTranslation($locale) ?? $route;

            return $this->toRoute($route, $parameters, $absolute);
        }

        if (! is_null($this->missingNamedRouteResolver) &&
            ! is_null($url = call_user_func($this->missingNamedRouteResolver, $name, $parameters, $absolute))) {
            return $url;
        }

        throw new RouteNotFoundException("Route [{$name}] not defined.");
    }

    public function action($action, $parameters = [], $absolute = true): string
    {
        if (is_null($route = $this->routes->getByAction($action = $this->formatAction($action)))) {
            throw new InvalidArgumentException("Action {$action} not defined.");
        }

        $locale = Arr::pull($parameters, 'lang', app()->getLocale());

        $route = $route->makeTranslation($locale) ?? $route;

        return $this->toRoute($route, $parameters, $absolute);
    }
}
