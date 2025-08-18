<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Matching\LocalizedUriValidator;
use Illuminate\Routing\Matching\UriValidator;
use Illuminate\Routing\Matching\ValidatorInterface;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;

class L10n
{
    /**
     * @return ValidatorInterface[]
     */
    public static function routeValidators(): array
    {
        return array_map(
            fn ($validator) => $validator instanceof UriValidator
                ? new LocalizedUriValidator
                : $validator,
            Route::getValidators()
        );
    }

    public static function registerTranslatedRoutes(): void
    {
        if (App::routesAreCached()) {
            return;
        }

        /** @var Router $router */
        $router = App::make(Router::class);

        foreach ($router->getRoutes() as $route) {
            /** @var Route $route */

            $locales = $route->getAction('lang') ?? [];

            $constraints = [];

            foreach ($locales as $locale => $uri) {
                if (is_int($locale)) {
                    $constraints[] = $uri;
                }
            }

            if ($constraints) {
                $constraints[] = App::getFallbackLocale();

                $route->whereIn('lang', $constraints);
            }
        }
    }
}
