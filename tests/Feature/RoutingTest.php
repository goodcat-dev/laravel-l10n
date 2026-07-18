<?php

use Goodcat\L10n\Contracts\LocalizedRoute;
use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\SetLocale;
use Goodcat\L10n\Tests\Support\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Translation\Translator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use function Pest\Laravel\get;

it('generates localized routes', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::lang(['es', 'it'])->group(function () {
        Route::get('/example', fn () => 'Hello, World!');
    });

    app(L10n::class)->registerLocalizedRoutes();

    foreach (['/example', '/es/ejemplo', '/it/example'] as $url) {
        get($url)->assertOk();
    }
});

it('detects and set the route locale', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetLocale::class)
        ->lang(['en', 'es', 'it'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    foreach (['en' => '/en/example', 'es' => '/es/ejemplo', 'it' => '/it/example'] as $locale => $url) {
        get($url)->assertOk();

        expect(app()->getLocale())->toBe($locale);
    }
});

it('generates localized routes without prefix', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => 'no_prefix']);

    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    get('/ejemplo')->assertOk();
});

it('prefixes the fallback locale without registering an unprefixed route', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => 'prefix']);

    $route = Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetLocale::class)
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    get('/example')->assertNotFound();
    get('/en/example')->assertOk();
    get('/es/ejemplo')->assertOk();

    expect(Route::getRoutes()->getByName('example'))->toBe($route);
});

it('reindexes a prefixed fallback route in the route collection', function () {
    config(['l10n.route_strategy' => 'prefix']);

    $routeCount = count(Route::getRoutes()->getRoutes());

    Route::get('/admin/example', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

    $getRoutes = array_keys($routes->getRoutesByMethod()['GET']);

    expect($getRoutes)
        ->toContain('en/admin/example')
        ->and(in_array('admin/example', $getRoutes, true))
        ->toBeFalse();

    // Reindexing must replace the old entry instead of duplicating it.
    expect($routes->getRoutes())
        ->toHaveCount($routeCount + 2);

    Route::setCompiledRoutes($routes->compile());

    get('/en/admin/example')->assertOk();
    get('/admin/example')->assertNotFound();
});

it('generates localized uri via helpers', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/example', Controller::class)
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    expect(route('example', ['lang' => 'es']))
        ->toBe('http://localhost/es/ejemplo')
        ->and(action(Controller::class, ['lang' => 'es']))
        ->toBe('http://localhost/es/ejemplo');
});

it('generates localized uri via helpers with scalar parameters', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/example/{id}', Controller::class)
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    expect(route('example', 5))
        ->toBe('http://localhost/example/5')
        ->and(action(Controller::class, 5))
        ->toBe('http://localhost/example/5');
});

it('does not consume the lang attribute of a model passed as parameter', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/example/{post}', Controller::class)
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    $post = new class(['id' => 7, 'lang' => 'es']) extends Model
    {
        protected $guarded = [];
    };

    expect(route('example', $post))
        ->toBe('http://localhost/example/7')
        ->and($post->getAttributes())
        ->toBe(['id' => 7, 'lang' => 'es']);
});

it('generates localized domains', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::domain('example.com')->lang(['es', 'it'])->group(function () {
        Route::get('/example', fn () => 'Hello, World!');
    });

    app(L10n::class)->registerLocalizedRoutes();

    get('http://es.example.com/ejemplo')->assertOk();
    get('http://example.com/it/example')->assertOk();
});

it('translates domains without adding route prefixes', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => 'no_prefix']);

    Route::domain('example.com')->lang(['es'])->group(function () {
        Route::get('/example', fn () => 'Hello, World!')->name('example');
    });

    app(L10n::class)->registerLocalizedRoutes();

    get('http://example.com/example')->assertOk();
    get('http://es.example.com/ejemplo')->assertOk();
});

it('prefixes the fallback locale when other locales translate the domain', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => 'prefix']);

    Route::domain('example.com')->lang(['es'])->group(function () {
        Route::get('/example', fn () => 'Hello, World!')->name('example');
    });

    app(L10n::class)->registerLocalizedRoutes();

    get('http://example.com/en/example')->assertOk();
    get('http://example.com/example')->assertNotFound();
    get('http://es.example.com/ejemplo')->assertOk();
});

it('matches route name against canonical route', function (bool $withCachedRoutes) {
    $matches = false;

    Route::get('/example', function () use (&$matches) {
        $matches = Goodcat\L10n\Facades\L10n::is('example');
    })
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    if ($withCachedRoutes) {
        /** @var RouteCollection $routes */
        $routes = Route::getRoutes();

        Route::setCompiledRoutes($routes->compile());
    }

    get('es/example')->assertOk();

    expect($matches)->toBeTrue();
})->with([
    'RouteCollection' => [false],
    'CompiledRouteCollection' => [true],
]);

it('registers localized routes idempotently', function (string $strategy) {
    config(['l10n.route_strategy' => $strategy]);

    $route = Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetLocale::class)
        ->lang(['es', 'it'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    $count = count(app(Router::class)->getRoutes()->getRoutes());
    $uri = $route->uri();

    app(L10n::class)->registerLocalizedRoutes();

    expect(app(Router::class)->getRoutes()->getRoutes())
        ->toHaveCount($count)
        ->and($route->uri())
        ->toBe($uri);
})->with([
    'no prefix' => ['no_prefix'],
    'prefix' => ['prefix'],
    'prefix except default' => ['prefix_except_default'],
]);

test('L10n::registerLocalizedRoutes leaves non-localized routes untouched', function () {
    Route::get('/plain', fn () => 'Hello, World!')->name('plain');

    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

    $routes->refreshNameLookups();

    expect($routes->getByName('plain')->getAction('key'))
        ->toBeNull()
        ->and($routes->getByName('example')->getAction('key'))
        ->not->toBeNull()
        ->and($routes->getByName('example.es')->getAction('key'))
        ->toBeNull();
});

test('canonical() throws when the canonical route is not registered', function () {
    $route = Route::get('/orphan', fn () => 'Hello, World!');

    $route->setAction($route->getAction() + ['canonical' => 'missing-key']);

    expect(fn () => $route->canonical())
        ->toThrow(RouteNotFoundException::class, 'Canonical route [missing-key] not defined.');
});

test('localized route inherit properties from canonical route', function () {
    /** @var LocalizedRoute $canonical */
    $canonical = Route::get('/example/{id}', fn () => 'Hello, World!')
        ->lang(['it'])
        ->where('id', '[0-9]+')
        ->defaults('id', 1)
        ->withTrashed()
        ->block(30, 5);

    /** @var LocalizedRoute $fallbackCanonical */
    $fallbackCanonical = Route::fallback(fn () => 'Fallback')->lang(['it']);

    $localized = $canonical->makeTranslation('it');

    $fallbackLocalized = $fallbackCanonical->makeTranslation('it');

    expect($localized)
        ->not->toBeNull()
        ->and($localized->wheres)->toBe(['id' => '[0-9]+'])
        ->and($localized->defaults)->toBe(['id' => 1])
        ->and($localized->allowsTrashedBindings())->toBeTrue()
        ->and($localized->locksFor())->toBe(30)
        ->and($localized->waitsFor())->toBe(5);

    expect($fallbackLocalized)
        ->not->toBeNull()
        ->and($fallbackLocalized->isFallback)->toBeTrue();
});
