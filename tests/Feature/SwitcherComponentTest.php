<?php

use Goodcat\L10n\L10n;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Translation\Translator;

use function Pest\Laravel\get;

it('renders a locale switcher for the current route', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/products/{product}', fn () => Blade::render('<x-l10n::switcher />'))
        ->lang(['es', 'it'])
        ->name('products.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = get('/es/productos/42');

    $response->assertOk();

    $response->assertSeeHtml([
        '<select onchange="window.location = this.value">',
        '<option value="http://localhost/products/42" >en</option>',
        '<option value="http://localhost/es/productos/42" selected>es</option>',
        '<option value="http://localhost/it/products/42" >it</option>',
        '</select>',
    ]);
});

it('preserves the current query string without overriding route parameters', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/products/{product}', fn () => Blade::render('<x-l10n::switcher />'))
        ->lang(['es', 'it'])
        ->name('products.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = get('/es/productos/42?product=wrong&tracking=keep-me&filters[color]=green');

    $response->assertOk();

    $response->assertSeeHtml([
        '<option value="http://localhost/products/42?tracking=keep-me&amp;filters%5Bcolor%5D=green" >en</option>',
        '<option value="http://localhost/es/productos/42?tracking=keep-me&amp;filters%5Bcolor%5D=green" selected>es</option>',
        '<option value="http://localhost/it/products/42?tracking=keep-me&amp;filters%5Bcolor%5D=green" >it</option>',
    ]);
});

it('renders nothing for a route without translations', function () {
    Route::get('/about', fn () => Blade::render('<x-l10n::switcher />'))
        ->name('about');

    $response = get('/about');

    $response->assertOk();

    $response->assertDontSee('<select', false);
});
