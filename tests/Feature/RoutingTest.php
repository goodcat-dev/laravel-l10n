<?php

use Goodcat\L10n\Contracts\LocalizedRoute;
use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\SetLocale;
use Goodcat\L10n\Tests\Support\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
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

    foreach (['en' => '/example', 'es' => '/es/ejemplo', 'it' => '/it/example'] as $locale => $url) {
        get($url)->assertOk();

        expect(app()->getLocale())->toBe($locale);
    }
});

it('skips a translation that collides with the canonical route', function () {
    config(['l10n.route_strategy' => 'no_prefix']);

    Route::get('/untranslated', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('untranslated');

    app(L10n::class)->registerLocalizedRoutes();

    get('/untranslated')->assertOk();

    expect(app(Router::class)->getRoutes()->getByName('untranslated'))
        ->not->toBeNull()
        ->and(app(Router::class)->getRoutes()->getByName('untranslated.es'))
        ->toBeNull()
        ->and(route('untranslated', ['lang' => 'es']))
        ->toBe('http://localhost/untranslated');
});

it('skips a translation that collides with the canonical route apart from slashes', function () {
    config(['l10n.route_strategy' => 'no_prefix']);

    app(Translator::class)->addLines(['routes.untranslated' => '/untranslated/'], 'es');

    Route::get('/untranslated', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('untranslated');

    app(L10n::class)->registerLocalizedRoutes();

    expect(app(Router::class)->getRoutes()->getByName('untranslated.es'))->toBeNull();
});

it('registers an untranslated slug when its domain is translated', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => 'no_prefix']);

    Route::domain('example.com')->get('/untranslated', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('untranslated');

    app(L10n::class)->registerLocalizedRoutes();

    expect(app(Router::class)->getRoutes()->getByName('untranslated.es')?->getDomain())
        ->toBe('es.example.com');
});

it('records the registered translations on the canonical route', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => 'no_prefix']);

    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    $routes = app(Router::class)->getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('example')?->getAction('translations'))
        ->toBe(['es' => $routes->getByName('example.es')?->getName()])
        ->and($routes->getByName('example.es')?->getAction('translations'))
        ->toBeNull();
});

it('names anonymous canonical routes and their translations', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => 'no_prefix']);

    $canonical = Route::get('/example', fn () => 'Hello, World!')->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    $name = $canonical->getName();

    $translationName = $canonical->getAction('translations')['es'];

    expect([$name, $translationName])
        ->each->toMatch('/^generated::[a-zA-Z0-9]+$/')
        ->and($translationName)->not->toBe($name);

});

it('records an empty translation map when every translation collides', function () {
    config(['l10n.route_strategy' => 'no_prefix']);

    Route::get('/untranslated', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('untranslated');

    app(L10n::class)->registerLocalizedRoutes();

    $routes = app(Router::class)->getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('untranslated')?->getAction('translations'))
        ->toBe([]);
});

it('leaves the routes untouched when registered twice', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => 'prefix']);

    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    $uris = collect(app(Router::class)->getRoutes()->getRoutes())->map->uri()->all();

    app(L10n::class)->registerLocalizedRoutes();

    expect(collect(app(Router::class)->getRoutes()->getRoutes())->map->uri()->all())
        ->toBe($uris);
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

it('normalizes the slashes of a translated uri', function () {
    config(['l10n.route_strategy' => 'no_prefix']);

    app(Translator::class)->addLines(['routes.example' => '/ejemplo/'], 'es');

    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    expect(app(Router::class)->getRoutes()->getByName('example.es')?->uri())
        ->toBe('ejemplo');

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

it('does not register a translation for the fallback locale listed in lang()', function (string $strategy) {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => $strategy]);

    $routeCount = count(Route::getRoutes()->getRoutes());

    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['en', 'es']);

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

    $routes->refreshNameLookups();

    // Only the Spanish translation is added: the canonical already serves 'en'.
    expect($routes->getRoutes())
        ->toHaveCount($routeCount + 2)
        ->and($routes->getByName('example.es'))
        ->not->toBeNull()
        ->and($routes->getByName('example.en'))
        ->toBeNull();
})->with([
    'no prefix' => ['no_prefix'],
    'prefix' => ['prefix'],
    'prefix except default' => ['prefix_except_default'],
]);

it('generates localized uri via helpers', function (bool $withCachedRoutes) {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/example', Controller::class)
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    if ($withCachedRoutes) {
        /** @var RouteCollection $routes */
        $routes = Route::getRoutes();

        Route::setCompiledRoutes($routes->compile());
    }

    expect(route('example', ['lang' => 'es']))
        ->toBe('http://localhost/es/ejemplo')
        ->and(action(Controller::class, ['lang' => 'es']))
        ->toBe('http://localhost/es/ejemplo');
})->with([
    'RouteCollection' => [false],
    'CompiledRouteCollection' => [true],
]);

it('uses the requested locale or preserves the explicitly localized route', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/example', Controller::class)
        ->name('example')
        ->lang(['es', 'it']);

    app(L10n::class)->registerLocalizedRoutes();

    expect(route('example'))
        ->toBe('http://localhost/example')
        ->and(route('example', ['lang' => 'it']))
        ->toBe('http://localhost/it/example')
        ->and(route('example.it'))
        ->toBe('http://localhost/it/example')
        ->and(route('example.it', ['lang' => 'es']))
        ->toBe('http://localhost/es/ejemplo')
        ->and(route('example.it', ['lang' => 'en']))
        ->toBe('http://localhost/example')
        ->and(route('example.it', ['lang' => 'de']))
        ->toBe('http://localhost/it/example');

    app()->setLocale('es');

    expect(route('example'))
        ->toBe('http://localhost/es/ejemplo')
        ->and(action(Controller::class))
        ->toBe('http://localhost/es/ejemplo')
        ->and(route('example.it'))
        ->toBe('http://localhost/it/example');
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

it('keeps the custom binding key on localized routes', function (string $strategy) {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => $strategy]);

    Route::get('/posts/{post:slug}', fn () => 'Hello, World!')
        ->name('posts.show')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

    $routes->refreshNameLookups();

    expect($routes->getByName('posts.show')->bindingFields())
        ->toBe(['post' => 'slug'])
        ->and($routes->getByName('posts.show.es')->bindingFields())
        ->toBe(['post' => 'slug']);
})->with([
    'no prefix' => ['no_prefix'],
    'prefix' => ['prefix'],
    'prefix except default' => ['prefix_except_default'],
]);

it('generates localized uri with the custom binding key', function (string $strategy, string $canonical, string $translated) {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => $strategy]);

    Route::get('/posts/{post:slug}', fn () => 'Hello, World!')
        ->name('posts.show')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    $post = new class(['id' => 7, 'slug' => 'hello-world']) extends Model
    {
        protected $guarded = [];
    };

    expect(route('posts.show', $post))
        ->toBe($canonical)
        ->and(route('posts.show', ['post' => $post, 'lang' => 'es']))
        ->toBe($translated);
})->with([
    'no prefix' => ['no_prefix', 'http://localhost/posts/hello-world', 'http://localhost/articulos/hello-world'],
    'prefix' => ['prefix', 'http://localhost/en/posts/hello-world', 'http://localhost/es/articulos/hello-world'],
    'prefix except default' => ['prefix_except_default', 'http://localhost/posts/hello-world', 'http://localhost/es/articulos/hello-world'],
]);

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
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => $strategy]);

    $route = Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetLocale::class)
        ->lang(['es'])
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

    expect($routes->getByName('plain')->getAction('translations'))
        ->toBeNull()
        ->and($routes->getByName('example')->getAction('translations'))
        ->not->toBeNull()
        ->and($routes->getByName('example.es')->getAction('translations'))
        ->toBeNull();
});

test('locale() returns the locale served by the route', function () {
    $canonical = Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    $plain = Route::get('/plain', fn () => 'Hello, World!');

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

    $routes->refreshNameLookups();

    expect($canonical->locale())
        ->toBe('en')
        ->and($routes->getByName('example.es')->locale())
        ->toBe('es')
        ->and($plain->locale())
        ->toBe('en');
});

test('getTranslations() resolves the registered translations from any localized route', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => 'no_prefix']);

    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    $plain = Route::get('/plain', fn () => 'Hello, World!');

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

    $routes->refreshNameLookups();

    $canonical = $routes->getByName('example');
    $translated = $routes->getByName('example.es');

    expect($canonical->getTranslations())
        ->toBe(['en' => $canonical, 'es' => $translated])
        ->and($translated->getTranslations())
        ->toBe(['en' => $canonical, 'es' => $translated])
        ->and($translated->getTranslations('en'))
        ->toBe(['en' => $canonical])
        ->and($translated->getTranslations('es'))
        ->toBe(['es' => $translated])
        ->and($translated->getTranslations('es', 'de', 'en'))
        ->toBe(['es' => $translated, 'en' => $canonical])
        ->and($translated->getTranslations('de'))
        ->toBe([])
        ->and($plain->getTranslations())
        ->toBe(['en' => $plain])
        ->and($plain->getTranslations('es'))
        ->toBe([])
        ->and($canonical->getAction('translations'))
        ->toBe(['es' => 'example.es']);
});

test('getTranslations() resolves the registered translations with cached routes', function () {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => 'no_prefix']);

    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

    Route::setCompiledRoutes($routes->compile());

    $canonical = Route::getRoutes()->getByName('example');
    $translations = $canonical->getTranslations();

    expect(array_keys($translations))
        ->toBe(['en', 'es'])
        ->and($translations['en'])
        ->toBe($canonical)
        ->and($translations['es']->uri())
        ->toBe('ejemplo')
        ->and($translations['es']->getTranslations('en'))
        ->toBe(['en' => $canonical]);
});

test('getTranslations() throws when a registered translation is missing', function () {
    $route = Route::get('/orphan', fn () => 'Hello, World!');

    $route->setAction($route->getAction() + ['translations' => ['es' => 'missing-name']]);

    expect(fn () => $route->getTranslations())
        ->toThrow(RouteNotFoundException::class, 'Translated route [missing-name] not defined.');
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

test('translated route does not inherit canonical runtime state', function () {
    $canonical = Route::get('/example', Controller::class)
        ->lang(['it'])
        ->bind(Request::create('/example'));

    $controller = $canonical->getController();

    $localized = $canonical->makeTranslation('it');

    expect($localized)
        ->not->toBe($canonical)
        ->and($localized->hasParameters())->toBeFalse()
        ->and($localized->matches(Request::create('/it/example')))->toBeTrue()
        ->and($localized->matches(Request::create('/example')))->toBeFalse()
        ->and($localized->getController())->not->toBe($controller);

    expect(fn () => $localized->originalParameters())
        ->toThrow(LogicException::class, 'Route is not bound.');
});

it('signs and validates urls on non-localized routes', function () {
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

    Route::get('/verify', fn () => request()->hasValidSignature() ? 'valid' : 'invalid')
        ->name('verification.verify');

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => 1]);

    get($url)->assertOk()->assertContent('valid');

    get($url.'&tampered=1')->assertOk()->assertSee('invalid');
});

it('signs and validates urls on localized routes across locales', function () {
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    Route::get('/example', fn () => app()->getLocale().':'.(request()->hasValidSignature() ? 'valid' : 'invalid'))
        ->middleware(SetLocale::class)
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    $url = URL::temporarySignedRoute('example', now()->addMinutes(30), ['lang' => 'es']);

    expect($url)->toContain('/es/ejemplo');

    get($url)->assertOk()->assertSee('es:valid');

    get($url.'&tampered=1')->assertOk()->assertSee('invalid');
});
