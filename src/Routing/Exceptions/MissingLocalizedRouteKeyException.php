<?php

namespace Goodcat\L10n\Routing\Exceptions;

use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

class MissingLocalizedRouteKeyException extends UrlGenerationException
{
    /**
     * @param  list<int|string>  $parameters
     */
    public static function forParameters(Route $route, array $parameters): self
    {
        $parameters = array_map(
            fn (int|string $name) => is_int($name) ? $route->parameterNames()[$name] ?? $name : $name,
            $parameters,
        );

        return new self(sprintf(
            'Missing localized route %s for [Route: %s] [URI: %s] [Locale: %s] [Missing %s: %s].',
            Str::plural('key', count($parameters)),
            $route->getName(),
            $route->uri(),
            $route->locale(),
            Str::plural('parameter', count($parameters)),
            implode(', ', $parameters),
        ));
    }
}
