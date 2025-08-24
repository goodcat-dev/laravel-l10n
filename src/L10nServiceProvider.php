<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Routing\LocalizedUrlGenerator;
use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class L10nServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(fn () => L10n::registerLocalizedRoute());

        Event::listen(RouteMatched::class, function (RouteMatched $event) {
            $locale = $event->route->parameter(
                'lang',
                $event->route->getAction('locale')
            );

            if ($locale) {
                App::setLocale($locale);
            }

            app(LocalizedUrlGenerator::class)->defaults(['lang' => App::getLocale()]);
        });
    }

    public function register(): void
    {
        $this->app->singleton(L10n::class, fn () => new L10n);

        $this->app->singleton(LocalizedUrlGenerator::class, function ($app) {
            $routes = $app['router']->getRoutes();

            $app->instance('routes', $routes);

            return new LocalizedUrlGenerator(
                $routes,
                $app->rebinding('request', $this->requestRebinder()),
                $app['config']['app.asset_url']
            );
        });

        Route::macro('lang', function (?array $translations = null): Route|RouteTranslations {
            /** @var Route $this */

            $lang = $this->action['lang'] ?? [];

            if (is_array($lang)) {
                $lang = new RouteTranslations($lang);
            }

            $lang->addTranslations($translations ?? []);

            $this->action['lang'] = $lang;

            return is_null($translations) ? $lang : $this;
        });
    }

    protected function requestRebinder(): \Closure
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }
}
