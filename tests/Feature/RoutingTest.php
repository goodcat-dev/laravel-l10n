<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\SetLocale;
use Goodcat\L10n\Routing\LocalizedUrlGenerator;
use Goodcat\L10n\Tests\Support\Controller;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Translation\Translator;

it('generates localized routes', function () {
    app(Translator::class)->addPath(__DIR__ . '/../Support/lang');

    Route::get('{lang}/example', fn () => 'Hello, World!')
        ->lang([
            'fr', 'de',
            'es' => 'es/ejemplo',
            'it' => 'it/esempio',
        ]);

    app(L10n::class)->registerLocalizedRoutes();

    foreach (['/example', '/fr/exemple', '/de/example', '/es/ejemplo', '/it/esempio'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('generates localized routes with {lang} prefix', function () {
    app(Translator::class)->addPath(__DIR__ . '/../Support/lang');

    Route::group([
        'lang' => ['fr', 'de', 'es', 'it'],
        'prefix' => '{lang}',
    ], function () {
        Route::get('/example', fn () => 'Hello, World!')
            ->lang([
                'it' => '/esempio',
                'es' => '/ejemplo',
            ]);
    });

    app(L10n::class)->registerLocalizedRoutes();

    foreach (['/example', '/fr/exemple', '/de/example', '/es/ejemplo', '/it/esempio'] as $url) {

        $this->get($url)->assertOk();
    }
});

it('appends {lang} parameter if missing', function () {
    Route::get('/example', fn () => 'Hello, World!')
        ->lang([
            'fr', 'de',
            'es' => 'ejemplo',
            'it' => 'esempio',
        ]);

    app(L10n::class)->registerLocalizedRoutes();

    foreach (['/example', '/fr/example', '/de/example', 'es/ejemplo', 'it/esempio'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('hides default locale', function () {
    Config::set('l10n.hide_default_locale', false);

    Route::get('{lang}/example', fn () => 'Hello, World!')
        ->lang(['de']);

    app(L10n::class)->registerLocalizedRoutes();

    $this->get('/en/example')->assertOk();

    $this->get('/example')->assertNotFound();
});

it('hides alias locale', function () {
    Config::set('l10n.hide_alias_locale', false);

    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es', 'it' => 'esempio']);

    app(L10n::class)->registerLocalizedRoutes();

    foreach (['/example', '/es/example', 'it/esempio'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('guess localized route name', function () {
    $route = Route::get('{lang}/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang([
            'fr', 'de',
            'es' => 'es/ejemplo',
            'it' => 'it/esempio',
        ]);

    app(L10n::class)->registerLocalizedRoutes();

    expect($route->getLocalizedName('it'))
        ->toBe('example#it')
        ->and($route->getLocalizedName('gr'))
        ->toBeNull()
        ->and($route->getLocalizedName('fr'))
        ->toBe('example');

});

it('can generate translated urls', function () {
    Route::get('{lang}/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang([
            'fr', 'de',
            'es' => 'es/ejemplo',
            'it' => 'it/esempio',
        ]);

    Route::get('/', fn () => 'Hello, World!')
        ->name('home');

    app(L10n::class)->registerLocalizedRoutes();

    app()->setLocale('de');

    expect(route('example', ['lang' => 'it']))
        ->toBe('http://localhost/it/esempio')
        ->and(route('example'))
        ->toBe('http://localhost/de/example')
        ->and(route('home'))
        ->toBe('http://localhost');
});

it('detects and set the route locale', function () {
    Route::get('{lang}/example', fn () => 'Hello, World!')
        ->middleware(SetLocale::class)
        ->name('example')
        ->lang([
            'fr', 'de',
            'es' => 'es/ejemplo',
            'it' => 'it/esempio',
        ]);

    app(L10n::class)->registerLocalizedRoutes();

    $this->get('es/ejemplo');

    expect(app()->getLocale())->toBe('es');

    $this->get('de/example');

    expect(app()->getLocale())->toBe('de');
});

it('removes a route from the route collection', function () {
    $route = Route::get('/example', Controller::class)->name('example');

    $collection = app(Router::class)->getRoutes();

    $collection->refreshNameLookups();
    $collection->refreshActionLookups();

    app(L10n::class)->forgetRoute($route, $collection);

    expect($collection->hasNamedRoute('example'))
        ->toBeFalse()
        ->and($collection->getByAction($route->getActionName()))
        ->toBeNull();
});
