<?php

use Goodcat\L10n\L10n;
use Illuminate\Support\Facades\Route;

it('generates localized routes', function () {
    Route::get('{lang}/example', fn () => 'Hello, World!')
        ->lang([
            'fr', 'de',
            'es' => 'es/ejemplo',
            'it' => 'it/esempio'
        ]);

    L10n::registerLocalizedRoute();

    foreach (['/example', '/fr/example', '/de/example', '/es/ejemplo', '/it/esempio'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('generates localized routes with lang prefix', function () {
    Route::group([
        'prefix' => '{lang}',
        'lang' => ['fr', 'de', 'es', 'it'],
    ], function () {
        Route::get('/example', fn () => 'Hello, World!')
            ->lang([
                'it' => '/esempio',
                'es' => '/ejemplo'
            ]);
    });

    L10n::registerLocalizedRoute();

    foreach (['/example', '/fr/example', '/de/example', '/es/ejemplo', '/it/esempio'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('hides default locale', function () {
    L10n::$hideDefaultLocale = false;

    Route::get('{lang}/example', fn () => 'Hello, World!')
        ->lang(['de']);

    L10n::registerLocalizedRoute();

    $this->get('/en/example')->assertOk();
});

it('guess localized route name', function () {
    $route = Route::get('{lang}/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang([
            'fr', 'de',
            'es' => 'es/ejemplo',
            'it' => 'it/esempio'
        ]);

    L10N::registerLocalizedRoute();

    $localizedName = $route->getLocalizedName('it');

    expect($localizedName)->toBe('example#it');

    $missingName = $route->getLocalizedName('gr');

    expect($missingName)->toBeNull();

    $baseName = $route->getLocalizedName('fr');

    expect($baseName)->toBe('example');
});
