<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;

/**
 * @mixin Router
 */
class LocalizedRouter
{
    /**
     * @return Closure(list<string>=): RouteRegistrar
     */
    public function lang(): Closure
    {
        return function (array $translations = []): RouteRegistrar {
            return (new RouteRegistrar(app(Router::class)))->lang($translations);
        };
    }
}
