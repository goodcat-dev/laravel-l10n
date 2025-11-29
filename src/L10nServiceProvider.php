<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Listeners\RegisterLocalizedViewsPath;
use Goodcat\L10n\Mixin\LocalizedApplication;
use Goodcat\L10n\Mixin\LocalizedRoute;
use Goodcat\L10n\Routing\LocalizedUrlGenerator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class L10nServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/l10n.php' => config_path('l10n.php'),
        ]);

        $this->app->booted(fn () => app(L10n::class)->registerLocalizedRoutes());

        Event::listen(LocaleUpdated::class, RegisterLocalizedViewsPath::class);
    }

    /**
     * @throws \ReflectionException
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/l10n.php', 'l10n');

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

        Application::mixin(new LocalizedApplication);
        Route::mixin(new LocalizedRoute);

        /*
        | The RouteRegistrar::class doesn't implement
        | the Macroable trait on Laravel v11.
        | I'll wait for the next major release.
       */

//        $langMixin = new LangMixin;

//        RouteRegistrar::mixin($langMixin);
//        Router::mixin($langMixin);
    }

    protected function requestRebinder(): \Closure
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }
}
