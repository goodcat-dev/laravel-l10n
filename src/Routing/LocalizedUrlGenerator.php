<?php

namespace Goodcat\L10n\Routing;

use BackedEnum;
use Goodcat\L10n\L10n;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class LocalizedUrlGenerator extends UrlGenerator
{
    /**
     * @throws UrlGenerationException
     */
    public function route($name, $parameters = [], $absolute = true, ?string $locale = null): string
    {
        if ($name instanceof BackedEnum && ! is_string($name = $name->value)) {
            throw new InvalidArgumentException('Attribute [name] expects a string backed enum.');
        }

        $locale ??= App::getLocale();

        if (! is_null($route = $this->routes->getByName($name))) {
            $localized = $this->guessLocalizedRouteName($route, $locale);

            if ($localized !== $name) {
                $route = $this->routes->getByName($localized);
            }

            return $this->toRoute($route, $parameters, $absolute);
        }

        if (! is_null($this->missingNamedRouteResolver) &&
            ! is_null($url = call_user_func($this->missingNamedRouteResolver, $name, $parameters, $absolute, $locale))) {
            return $url;
        }

        throw new RouteNotFoundException("Route [{$name}] not defined.");
    }

    protected function guessLocalizedRouteName(Route $route, string $locale): string
    {
        $lang = $route->getAction('locale') ?? App::getLocale();

        $name = preg_replace("/#$lang$/", '', $route->getName());

        if ($route->lang()->hasAlias($locale)) {
            return "$name#$locale";
        }

        if (
            L10n::$hideDefaultLocale
            && $locale === App::getFallbackLocale()
        ) {
            return "$name#$locale";
        }

        return $name;
    }
}