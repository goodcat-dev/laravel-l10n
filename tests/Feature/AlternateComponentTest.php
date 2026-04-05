<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\SetLocale;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Translation\Translator;

it('renders alternate hreflang links for the current route', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/products/{product}', fn () => Blade::render('<x-l10n::alternate />'))
        ->lang(['es', 'it'])
        ->name('products.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = $this->get('/products/42');

    $response->assertOk();

    $response->assertSee([
        '<link rel="alternate" hreflang="en" href="http://localhost/products/42" />',
        '<link rel="alternate" hreflang="es" href="http://localhost/es/productos/42" />',
        '<link rel="alternate" hreflang="it" href="http://localhost/it/products/42" />',
        '<link rel="alternate" hreflang="x-default" href="http://localhost/products/42" />',
    ], false);
});

it('renders alternate hreflang links from a localized route', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/products/{product}', fn () => Blade::render('<x-l10n::alternate />'))
        ->lang(['es', 'it'])
        ->name('products.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = $this->get('/es/productos/42');

    $response->assertOk();

    $response->assertSee([
        '<link rel="alternate" hreflang="en" href="http://localhost/products/42" />',
        '<link rel="alternate" hreflang="es" href="http://localhost/es/productos/42" />',
        '<link rel="alternate" hreflang="it" href="http://localhost/it/products/42" />',
        '<link rel="alternate" hreflang="x-default" href="http://localhost/products/42" />',
    ], false);
});

it('renders nothing for a route without translations', function () {
    Route::get('/about', fn () => Blade::render('<x-l10n::alternate />'))
        ->name('about');

    $response = $this->get('/about');

    $response->assertOk();

    $response->assertDontSee('<link', false);
});
