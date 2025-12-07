<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;

class LocalizedRouter
{
    public function forget(): Closure
    {
        return function (Route $route): void {
            /** @var Router $this */

            $collection = $this->getRoutes();

            $forget = Closure::bind(function (Route $route): void {
                $methods = $route->methods();
                $domainAndUri = $route->getDomain().$route->uri();

                foreach ($methods as $method) {
                    unset($this->routes[$method][$domainAndUri]);
                }

                unset($this->allRoutes[implode('|', $methods).$domainAndUri]);

                if ($name = $route->getName()) {
                    unset($this->nameList[$name]);
                }

                unset($this->actionList[$route->getActionName()]);
            }, $collection, RouteCollection::class);

            $forget($route);
        };
    }
}