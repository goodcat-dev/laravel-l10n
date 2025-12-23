<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\SetLocale;
use Goodcat\L10n\Tests\Support\Controller;
use Illuminate\Routing\Router;
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

it('generates localized uri via helpers', function () {
    app(Translator::class)->addPath(__DIR__ . '/../Support/lang');

    Route::get('/example', Controller::class)
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    expect(route('example', ['lang' => 'es']))
        ->toBe('http://localhost/es/ejemplo')
        ->and(action(Controller::class, ['lang' => 'es']))
        ->toBe('http://localhost/es/ejemplo');
});

it('generates localized domains', function () {
    app(Translator::class)->addPath(__DIR__ . '/../Support/lang');

    Route::domain('example.com')->lang(['es', 'it'])->group(function () {
        Route::get('/example', fn () => 'Hello, World!');
    });

    app(L10n::class)->registerLocalizedRoutes();

    $this->get('http://es.example.com/es/ejemplo')->assertOk();
    $this->get('http://example.com/it/example')->assertOk();
});

it('matches route name against canonical route', function () {
    $matches = false;

    Route::get('/example', function () use (&$matches) {
        $matches = \Goodcat\L10n\Facades\L10n::is('example');
    })
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    $this->get('es/example')->assertOk();

    expect($matches)->toBeTrue();
});

test('L10n::registerLocalizedRoutes is idempotent', function () {
    Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetLocale::class)
        ->lang(['es', 'it'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    $count = app(Router::class)->getRoutes()->count();

    app(L10n::class)->registerLocalizedRoutes();

    expect(app(Router::class)->getRoutes()->count())->toBe($count);
});
