<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Mixin\LocalizedRoute;
use Goodcat\L10n\Routing\LocalizedUrlGenerator;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\FileViewFinder;

class L10nServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(fn() => L10n::registerLocalizedRoute());

        Event::listen(RouteMatched::class, function (RouteMatched $event) {
            $locale = $event->route->parameter(
                'lang',
                $event->route->getAction('locale')
            );

            if ($locale) {
                App::setLocale($locale);
            }
        });

        Event::listen(LocaleUpdated::class, function (LocaleUpdated $event) {
            /** @var FileViewFinder $finder */
            $finder = \app('view.finder');

            $paths = $finder->getPaths();

            $index = array_search(L10n::$localizedViewsPath, $paths, true);

            if ($index !== false) {
                unset($paths[$index]);

                $finder->setPaths($paths);
            }

            $newPath = \resource_path('views/' . $event->locale);

            L10n::$localizedViewsPath = is_dir($newPath) ? $newPath : '';

            if (L10n::$localizedViewsPath) {
                $finder->prependLocation($newPath);
            }
        });
    }

    /**
     * @throws \ReflectionException
     */
    public function register(): void
    {
        $this->app->singleton(LocalizedUrlGenerator::class, function ($app) {
            $routes = $app['router']->getRoutes();

            $app->instance('routes', $routes);

            return new LocalizedUrlGenerator(
                $routes,
                $app->rebinding('request', $this->requestRebinder()),
                $app['config']['app.asset_url']
            );
        });

        Route::mixin(new LocalizedRoute);
    }

    protected function requestRebinder(): \Closure
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }
}
