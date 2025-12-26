<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Illuminate\Routing\CompiledRouteCollection;
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
            $collection = $this->getRoutes();

            $getByKey = Closure::bind(function (string $key): ?Route {
                $attributes = array_find(
                    $this->attributes,
                    fn ($route) => $route['action']['key'] === $key
                );

                return $attributes ? $this->newRoute($attributes) : null;
            }, $collection, CompiledRouteCollection::class);

            if ($collection instanceof RouteCollection) {
                $getByKey = Closure::bind(function (string $key): ?Route {
                    return $this->allRoutes[$key] ?? null;
                }, $collection, RouteCollection::class);
            }

            return $getByKey($key);
        };
    }
}
