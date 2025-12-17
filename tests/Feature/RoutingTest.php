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

    foreach (['en' => '/example', 'es' => '/es/ejemplo', 'it' => '/it/example'] as $locale => $url) {
        $this->get($url)->assertOk();

        expect(app()->getLocale())->toBe($locale);
    }
});

it('generates localized routes without prefix', function () {
    app(Translator::class)->addPath(__DIR__ . '/../Support/lang');

    config(['l10n.add_locale_prefix' => false]);

    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    $this->get('/ejemplo')->assertOk();
});

it('generates localized uri', function () {
    app(Translator::class)->addPath(__DIR__ . '/../Support/lang');

    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    expect(route('example', ['lang' => 'es']))->toBe('http://localhost/es/ejemplo');
});
