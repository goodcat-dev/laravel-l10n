<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\SetLocale;
use Goodcat\L10n\Routing\LocalizedUrlGenerator;
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
        'prefix' => '{lang}',
        'lang' => ['fr', 'de', 'es', 'it'],
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
    app(Translator::class)->addPath(__DIR__ . '/../Support/lang');

    Route::get('/example', fn () => 'Hello, World!')
        ->lang([
            'fr', 'de',
            'es' => 'ejemplo',
            'it' => 'esempio',
        ]);

    app(L10n::class)->registerLocalizedRoutes();

    foreach (['/example', '/fr/exemple', '/de/example', '/es/ejemplo', '/it/esempio'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('hides default locale', function () {
    L10n::$hideDefaultLocale = false;

    Route::get('{lang}/example', fn () => 'Hello, World!')
        ->lang(['de']);

    app(L10n::class)->registerLocalizedRoutes();

    $this->get('/en/example')->assertOk();

    $this->get('/example')->assertNotFound();

    L10n::$hideDefaultLocale = true;
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

    $localizedName = $route->getLocalizedName('it');

    expect($localizedName)->toBe('example#it');

    $missingName = $route->getLocalizedName('gr');

    expect($missingName)->toBeNull();

    $baseName = $route->getLocalizedName('fr');

    expect($baseName)->toBe('example');
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

    /** @var LocalizedUrlGenerator $urlGenerator */
    $urlGenerator = app(LocalizedUrlGenerator::class);

    $localizedUrl = $urlGenerator->route('example', ['lang' => 'it']);

    expect($localizedUrl)->toBe('http://localhost/it/esempio');

    $url = $urlGenerator->route('home');

    expect($url)->toBe('http://localhost');
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
