<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;
use Illuminate\Translation\Translator;

it('generates localized routes', function () {
    app(Translator::class)->addPath(__DIR__ . '/../Support/lang');

    Route::lang(['es', 'it'])->group(function () {
        Route::get('/example', fn () => 'Hello, World!');
    });

    app(L10n::class)->registerLocalizedRoutes();

    foreach (['/example', '/es/ejemplo', '/it/example'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('detects and set the route locale', function () {
    app(Translator::class)->addPath(__DIR__ . '/../Support/lang');

    Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetLocale::class)
        ->lang(['es', 'it'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    $this->get('es/ejemplo');

    expect(app()->getLocale())->toBe('es');

    $this->get('it/example');

    expect(app()->getLocale())->toBe('it');
});
