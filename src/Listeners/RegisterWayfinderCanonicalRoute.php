<?php

namespace Goodcat\L10n\Listeners;

use Closure;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\RouteCollectionInterface;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;

class RegisterWayfinderCanonicalRoute
{
    public const MARKER = '__canonical';

    public function __invoke(CommandStarting $event): void
    {
        if ($event->command !== 'wayfinder:generate') {
            return;
        }

        $collection = app(Router::class)->getRoutes();

        $collection instanceof CompiledRouteCollection
            ? $this->handleCompiledRoutes($collection)
            : $this->handleRoutes($collection);
    }

    protected function handleCompiledRoutes(CompiledRouteCollection $collection): void
    {
        $marker = self::MARKER;

        $canonical = Closure::bind(function () use ($marker): void {
            foreach ($this->attributes as $name => &$attributes) {
                if (str_starts_with($name, 'generated::')
                    || ! Arr::has($attributes, 'action.lang')) {
                    continue;
                }

                Arr::set($attributes, 'action.as', "$name.$marker");
            }
        }, $collection, CompiledRouteCollection::class);

        $canonical();
    }

    protected function handleRoutes(RouteCollectionInterface $collection): void
    {
        foreach ($collection->getRoutes() as $route) {
            if ($route->getName()
                && $route->getAction('lang')
                && ! str_ends_with($route->getName(), '.'.self::MARKER)) {
                $route->name('.'.self::MARKER);
            }
        }
    }
}
