<?php

namespace Goodcat\L10n\Routing;

use BackedEnum;
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

        $locale = Arr::pull($parameters, 'lang', app()->getLocale());

        if (! is_null($route = $this->routes->getByName($name))) {
            $route = $route->makeTranslation($locale) ?? $route;

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

        $locale = Arr::pull($parameters, 'lang', app()->getLocale());

        $route = $route->makeTranslation($locale) ?? $route;

        return $this->toRoute($route, $parameters, $absolute);
    }
}
