<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Listeners\RegisterLocalizedViewsPath;
use Goodcat\L10n\Mixin\LocalizedApplication;
use Goodcat\L10n\Mixin\LocalizedRoute;
use Goodcat\L10n\Mixin\LangMixin;
use Goodcat\L10n\Routing\LocalizedUrlGenerator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class L10nServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(fn () => app(L10n::class)->registerLocalizedRoutes());

        Event::listen(LocaleUpdated::class, RegisterLocalizedViewsPath::class);
    }

    /**
     * @throws \ReflectionException
     */
    public function register(): void
    {
        $this->app->singleton(LocalizedUrlGenerator::class, function (Application $app) {
            $routes = $app['router']->getRoutes();

            $app->instance('routes', $routes);

            return new LocalizedUrlGenerator(
                $routes,
                $app->rebinding('request', $this->requestRebinder()),
                $app['config']['app.asset_url']
            );
        });

        Application::mixin(new LocalizedApplication);
        Route::mixin(new LocalizedRoute);

        $langMixin = new LangMixin;

        RouteRegistrar::mixin($langMixin);
        Router::mixin($langMixin);
    }

    protected function requestRebinder(): \Closure
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }
}
