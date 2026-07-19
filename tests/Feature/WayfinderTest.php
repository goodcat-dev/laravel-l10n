<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Listeners\RegisterWayfinderCanonicalRoute;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Translation\Translator;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

it('does not rename routes for other commands', function () {
    $route = Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    (new RegisterWayfinderCanonicalRoute)(
        new CommandStarting('route:list', new StringInput(''), new NullOutput)
    );

    expect($route->getName())->toBe('example');
});

it('renames canonical routes', function () {
    $localized = Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    $vanilla = Route::get('/vanilla', fn () => 'Hello, World!')
        ->name('vanilla');

    app(L10n::class)->registerLocalizedRoutes();

    (new RegisterWayfinderCanonicalRoute)(
        new CommandStarting('wayfinder:generate', new StringInput(''), new NullOutput)
    );

    expect($localized->getName())
        ->toBe('example.__canonical')
        ->and($vanilla->getName())
        ->toBe('vanilla');
});

it('does not duplicate the canonical route name when the fallback locale is in lang()', function () {
    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['en', 'fr']);

    app(L10n::class)->registerLocalizedRoutes();

    (new RegisterWayfinderCanonicalRoute)(
        new CommandStarting('wayfinder:generate', new StringInput(''), new NullOutput)
    );

    $names = array_filter(array_map(
        fn ($route) => $route->getName(),
        Route::getRoutes()->getRoutes()
    ));

    expect(array_count_values($names)['example.__canonical'])->toBe(1);
});

it('renames canonical cached routes', function (string $strategy) {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => $strategy]);

    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    Route::get('/vanilla', fn () => 'Hello, World!')
        ->name('vanilla');

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

    Route::setCompiledRoutes($routes->compile());

    (new RegisterWayfinderCanonicalRoute)(
        new CommandStarting('wayfinder:generate', new StringInput(''), new NullOutput)
    );

    $routes = app(Router::class)->getRoutes();

    expect($routes->getByName('example')?->getAction('as'))
        ->toBe('example.__canonical')
        ->and($routes->getByName('vanilla')?->getAction('as'))
        ->toBe('vanilla');
})->with([
    'no prefix' => ['no_prefix'],
    'prefix' => ['prefix'],
    'prefix except default' => ['prefix_except_default'],
]);
