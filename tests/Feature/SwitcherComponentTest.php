<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Tests\Support\Post;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

it('renders a locale switcher for the current route', function () {
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

it('uses the localized route key of each locale', function () {
    $post = new Post(['slug' => ['en' => 'hello-world', 'es' => 'hola-mundo']]);

    Route::bind('post', fn () => $post);

    Route::get('/posts/{post:slug}', fn () => Blade::render('<x-l10n::switcher />'))
        ->middleware('api')
        ->lang(['es'])
        ->name('posts.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = get('/es/articulos/hola-mundo');

    $response->assertOk();

    $response->assertSeeHtml([
        '<option value="http://localhost/posts/hello-world" >en</option>',
        '<option value="http://localhost/es/articulos/hola-mundo" selected>es</option>',
    ]);
});

it('skips the locales without a localized route key', function () {
    $post = new Post(['slug' => ['en' => 'hello-world', 'es' => 'hola-mundo']]);

    Route::bind('post', fn () => $post);

    Route::get('/posts/{post:slug}', fn () => Blade::render('<x-l10n::switcher />'))
        ->middleware('api')
        ->lang(['es', 'it'])
        ->name('posts.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = get('/es/articulos/hola-mundo');

    $response->assertOk();

    $response->assertSeeHtml([
        '<option value="http://localhost/posts/hello-world" >en</option>',
        '<option value="http://localhost/es/articulos/hola-mundo" selected>es</option>',
    ]);

    $response->assertDontSee('>it</option>', false);
});

it('renders a locale switcher for an optional localized route key', function (string $uri, string $canonical, string $translated) {
    Route::bind('post', fn () => new Post(['slug' => ['en' => 'hello-world', 'es' => 'hola-mundo']]));

    Route::get('/posts/{post:slug?}', fn () => Blade::render('<x-l10n::switcher />'))
        ->middleware('api')
        ->lang(['es'])
        ->name('posts.show');

    app(L10n::class)->registerLocalizedRoutes();

    $response = get($uri);

    $response->assertOk();

    $response->assertSeeHtml([
        "<option value=\"$canonical\" >en</option>",
        "<option value=\"$translated\" selected>es</option>",
    ]);
})->with([
    'without key' => ['/es/articulos', 'http://localhost/posts', 'http://localhost/es/articulos'],
    'with key' => ['/es/articulos/hola-mundo', 'http://localhost/posts/hello-world', 'http://localhost/es/articulos/hola-mundo'],
]);

it('renders nothing for a route without translations', function () {
    Route::get('/about', fn () => Blade::render('<x-l10n::switcher />'))
        ->name('about');

    $response = get('/about');

    $response->assertOk();

    $response->assertDontSee('<select', false);
});
