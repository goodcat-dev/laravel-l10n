<?php

namespace Goodcat\L10n;

use Closure;
use Goodcat\L10n\Listeners\RegisterLocalizedViewsPath;
use Goodcat\L10n\Listeners\RegisterWayfinderCanonicalRoute;
use Goodcat\L10n\Mixin\LocalizedApplication;
use Goodcat\L10n\Mixin\LocalizedRoute;
use Goodcat\L10n\Mixin\LocalizedRouter;
use Goodcat\L10n\Mixin\LocalizedRouteRegistrar;
use Goodcat\L10n\Routing\LocalizedUrlGenerator;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use ReflectionException;

class L10nServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/l10n.php' => config_path('l10n.php')], 'l10n-config');

        $this->publishes([
            __DIR__.'/../stubs/route.js' => resource_path('js/routes.js'),
            __DIR__.'/../stubs/useLaravelL10n.ts' => resource_path('js/composables/useLaravelL10n.ts'),
        ], 'l10n-ziggy');

        $this->app->booted(fn () => app(L10n::class)->registerLocalizedRoutes());

        Event::listen(LocaleUpdated::class, RegisterLocalizedViewsPath::class);
        Event::listen(CommandStarting::class, RegisterWayfinderCanonicalRoute::class);
    }

    /**
     * @throws ReflectionException
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/l10n.php', 'l10n');

        $this->app->singleton(L10n::class);

        $this->app->singleton(LocalizedUrlGenerator::class, function (Application $app) {
            $routes = $app['router']->getRoutes();

            $app->instance('routes', $routes);

            return new LocalizedUrlGenerator(
                $routes,
                $app->rebinding('request', $this->requestRebinder()),
                $app['config']['app.asset_url']
            );
        });

        $this->app->alias(LocalizedUrlGenerator::class, 'url');

        Router::mixin(new LocalizedRouter);
        RouteRegistrar::mixin(new LocalizedRouteRegistrar);
        Application::mixin(new LocalizedApplication);
        Route::mixin(new LocalizedRoute);
    }

    protected function requestRebinder(): Closure
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }
}
