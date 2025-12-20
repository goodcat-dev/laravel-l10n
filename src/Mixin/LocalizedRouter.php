<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;

class LocalizedRouter
{
    public function lang(): Closure
    {
        return function (array $translations = []): RouteRegistrar {
            /** @var Router $this */

            return (new RouteRegistrar($this))->lang($translations);
        };
    }

    public function getByKey(): Closure
    {
        return function (string $key): ?Route {
            $getByKey = Closure::bind(function (string $key): ?Route {
                return $this->allRoutes[$key] ?? null;
            }, $this->getRoutes(), RouteCollection::class);

            return $getByKey($key);
        };
    }
}