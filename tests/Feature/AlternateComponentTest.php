<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Tests\Support\Post;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

it('renders alternate hreflang links for the current route', function () {
    Route::get('/products/{product}', fn () => Blade::render('<x-l10n::alternate />'))
        ->lang(['es', 'it'])
        ->name('products.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = get('/products/42');

    $response->assertOk();

    $response->assertSee([
        '<link rel="alternate" hreflang="en" href="http://localhost/products/42" />',
        '<link rel="alternate" hreflang="es" href="http://localhost/es/productos/42" />',
        '<link rel="alternate" hreflang="it" href="http://localhost/it/products/42" />',
        '<link rel="alternate" hreflang="x-default" href="http://localhost/products/42" />',
    ], false);
});

it('renders alternate hreflang links from a localized route', function () {
    Route::get('/products/{product}', fn () => Blade::render('<x-l10n::alternate />'))
        ->lang(['es', 'it'])
        ->name('products.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = get('/es/productos/42?page=2');

    $response->assertOk();

    $response->assertSee([
        '<link rel="alternate" hreflang="en" href="http://localhost/products/42?page=2" />',
        '<link rel="alternate" hreflang="es" href="http://localhost/es/productos/42?page=2" />',
        '<link rel="alternate" hreflang="it" href="http://localhost/it/products/42?page=2" />',
        '<link rel="alternate" hreflang="x-default" href="http://localhost/products/42?page=2" />',
    ], false);
});

it('uses the localized route key of each locale', function () {
    $post = new Post(['slug' => ['en' => 'hello-world', 'es' => 'hola-mundo']]);

    Route::bind('post', fn () => $post);

    Route::get('/posts/{post:slug}', fn () => Blade::render('<x-l10n::alternate />'))
        ->middleware('api')
        ->lang(['es'])
        ->name('posts.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = get('/es/articulos/hola-mundo');

    $response->assertOk();

    $response->assertSee([
        '<link rel="alternate" hreflang="en" href="http://localhost/posts/hello-world" />',
        '<link rel="alternate" hreflang="es" href="http://localhost/es/articulos/hola-mundo" />',
        '<link rel="alternate" hreflang="x-default" href="http://localhost/posts/hello-world" />',
    ], false);
});

it('skips the locales without a localized route key', function () {
    $post = new Post(['slug' => ['es' => 'hola-mundo', 'it' => 'ciao-mondo']]);

    Route::bind('post', fn () => $post);

    Route::get('/posts/{post:slug}', fn () => Blade::render('<x-l10n::alternate />'))
        ->middleware('api')
        ->lang(['es', 'it'])
        ->name('posts.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = get('/es/articulos/hola-mundo');

    $response->assertOk();

    $response->assertSee([
        '<link rel="alternate" hreflang="es" href="http://localhost/es/articulos/hola-mundo" />',
        '<link rel="alternate" hreflang="it" href="http://localhost/it/posts/ciao-mondo" />',
    ], false);

    $response->assertDontSee(['hreflang="en"', 'hreflang="x-default"'], false);
});

it('renders nothing for a route without translations', function () {
    Route::get('/about', fn () => Blade::render('<x-l10n::alternate />'))
        ->name('about');

    $response = get('/about');

    $response->assertOk();

    $response->assertDontSee('<link', false);
});
