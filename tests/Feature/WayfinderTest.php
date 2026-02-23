<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Listeners\RegisterWayfinderCanonicalRoute;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
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
        ->toBe('example.en')
        ->and($vanilla->getName())
        ->toBe('vanilla');
});

it('renames canonical cached routes', function () {
    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    Route::get('/vanilla', fn () => 'Hello, World!')
        ->name('vanilla');

    app(L10n::class)->registerLocalizedRoutes();

    Route::setCompiledRoutes(Route::getRoutes()->compile());

    (new RegisterWayfinderCanonicalRoute)(
        new CommandStarting('wayfinder:generate', new StringInput(''), new NullOutput)
    );

    $routes = app(Router::class)->getRoutes();

    expect($routes->getByName('example')?->getAction('as'))
        ->toBe('example.en')
        ->and($routes->getByName('vanilla')?->getAction('as'))
        ->toBe('vanilla');
});
