<?php

namespace Goodcat\L10n\Routing;

use BackedEnum;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class LocalizedUrlGenerator extends UrlGenerator
{
    /**
     * @throws UrlGenerationException
     */
    public function route($name, $parameters = [], $absolute = true): string
    {
        if ($name instanceof BackedEnum && ! is_string($name = $name->value)) {
            throw new InvalidArgumentException('Attribute [name] expects a string backed enum.');
        }

        $locale = Arr::pull($parameters, 'lang', app()->getLocale());

        if (! is_null($route = $this->routes->getByName($name))) {
            $localized = $route->getLocalizedName($locale);

            if ($localized !== null && $localized !== $name) {
                $route = $this->routes->getByName($localized);
            }

            return parent::toRoute($route, $parameters, $absolute);
        }

        if (! is_null($this->missingNamedRouteResolver) &&
            ! is_null($url = call_user_func($this->missingNamedRouteResolver, $name, $parameters, $absolute))) {
            return $url;
        }

        throw new RouteNotFoundException("Route [{$name}] not defined.");
    }
}
