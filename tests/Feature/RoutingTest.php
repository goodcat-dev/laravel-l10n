<?php

use Goodcat\L10n\Contracts\LocalizedRoute;
use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\SetLocale;
use Goodcat\L10n\Tests\Support\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Translation\Translator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use function Pest\Laravel\get;

it('serves translations without prefix', function (string $url, int $status) {
    config(['l10n.route_strategy' => 'no_prefix']);

    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    get($url)->assertStatus($status);
})->with([
    'unprefixed canonical' => ['/example', 200],
    'unprefixed translated path' => ['/ejemplo', 200],
    'prefixed translated path' => ['/es/ejemplo', 404],
]);

it('serves every locale with prefix', function (string $url, int $status) {
    config(['l10n.route_strategy' => 'prefix']);

    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es', 'it'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    get($url)->assertStatus($status);
})->with([
    'prefixed canonical' => ['/en/example', 200],
    'prefixed translated path' => ['/es/ejemplo', 200],
    'prefixed untranslated path' => ['/it/example', 200],
    'unprefixed canonical' => ['/example', 404],
]);

it('serves translations with prefix except for the default locale', function (string $url, int $status) {
    config(['l10n.route_strategy' => 'prefix_except_default']);

    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es', 'it'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    get($url)->assertStatus($status);
})->with([
    'unprefixed canonical' => ['/example', 200],
    'prefixed translated path' => ['/es/ejemplo', 200],
    'prefixed untranslated path' => ['/it/example', 200],
    'prefixed canonical' => ['/en/example', 404],
]);

it('sets the locale served by the route', function (string $url, string $locale) {
    Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetLocale::class)
        ->lang(['es', 'it'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    get($url)->assertOk();

    expect(app()->getLocale())->toBe($locale);
})->with([
    'en' => ['/example', 'en'],
    'es' => ['/es/ejemplo', 'es'],
    'it' => ['/it/example', 'it'],
]);

it('uses the route locale for binding and restores it on the canonical route', function (string $group) {
    config(['app.key' => str_repeat('a', 32), 'session.driver' => 'array']);

    Route::bind('value', fn () => app()->getLocale());

    Route::get('example/{value}', function (Request $request, string $value) {
        return [$value, $request->getLocale()];
    })->middleware($group)->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    app()->setLocale('fr');

    get('/es/example/42')->assertExactJson(['es', 'es']);
    get('/example/42')->assertExactJson(['en', 'en']);
})->with(['web', 'api']);

it('preserves the application locale on routes without localization', function () {
    Route::get('plain', fn () => app()->getLocale())->middleware('api');

    app(L10n::class)->registerLocalizedRoutes();

    app()->setLocale('fr');

    get('/plain')->assertContent('fr');
});

it('skips a translation that collides with the canonical route', function () {
    config(['l10n.route_strategy' => 'no_prefix']);

    Route::get('/untranslated', fn () => app()->getLocale())
        ->middleware('api')
        ->lang(['es'])
        ->name('untranslated');

    app(L10n::class)->registerLocalizedRoutes();

    app()->setLocale('fr');

    get('/untranslated')->assertContent('en');

    expect(Route::getRoutes()->getByName('untranslated')?->getAction('translations'))
        ->toBe([])
        ->and(Route::getRoutes()->getByName('untranslated.es'))
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

    expect(Route::getRoutes()->getByName('untranslated.es'))->toBeNull();
});

it('registers an untranslated path when its domain is translated', function () {
    config(['l10n.route_strategy' => 'no_prefix']);

    Route::domain('example.com')->get('/untranslated', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('untranslated');

    app(L10n::class)->registerLocalizedRoutes();

    expect(Route::getRoutes()->getByName('untranslated.es')?->getDomain())
        ->toBe('es.example.com');
});

it('records the registered translations on the canonical route', function () {
    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('example');

    $plain = Route::get('/plain', fn () => 'Hello, World!');

    app(L10n::class)->registerLocalizedRoutes();

    expect(Route::getRoutes()->getByName('example')?->getAction('translations'))
        ->toBe(['es' => 'example.es'])
        ->and(Route::getRoutes()->getByName('example.es')?->getAction('translations'))
        ->toBeNull()
        ->and($plain->getAction('translations'))
        ->toBeNull();
});

it('names anonymous canonical routes and their translations', function () {
    config(['l10n.route_strategy' => 'no_prefix']);

    $canonical = Route::get('/example', fn () => 'Hello, World!')->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    $name = $canonical->getName();

    $translationName = $canonical->getAction('translations')['es'];

    expect([$name, $translationName])
        ->each->toMatch('/^generated::[a-zA-Z0-9]+$/')
        ->and($translationName)->not->toBe($name);
});

it('normalizes the slashes of a translated uri', function () {
    config(['l10n.route_strategy' => 'no_prefix']);

    app(Translator::class)->addLines(['routes.example' => '/ejemplo/'], 'es');

    Route::get('/example', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    expect(Route::getRoutes()->getByName('example.es')?->uri())
        ->toBe('ejemplo');

    get('/ejemplo')->assertOk();
});

it('reindexes the prefixed canonical route instead of duplicating it', function () {
    config(['l10n.route_strategy' => 'prefix']);

    $routeCount = count(Route::getRoutes()->getRoutes());

    $route = Route::get('/admin/example', fn () => 'Hello, World!')
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

    $getRoutes = array_keys($routes->getRoutesByMethod()['GET']);

    expect($getRoutes)
        ->toContain('en/admin/example')
        ->and($getRoutes)
        ->not->toContain('admin/example')
        ->and($routes->getRoutes())
        ->toHaveCount($routeCount + 2)
        ->and($routes->getByName('example'))
        ->toBe($route);

    Route::setCompiledRoutes($routes->compile());

    get('/en/admin/example')->assertOk();
    get('/admin/example')->assertNotFound();
});

it('does not register a translation for the fallback locale listed in lang()', function (string $strategy) {
    config(['l10n.route_strategy' => $strategy]);

    $routeCount = count(Route::getRoutes()->getRoutes());

    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['en', 'es']);

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

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

it('generates the url for the requested locale', function (string $name, array $parameters, string $expected) {
    Route::get('/example', Controller::class)
        ->name('example')
        ->lang(['es', 'it']);

    app(L10n::class)->registerLocalizedRoutes();

    expect(route($name, $parameters))->toBe($expected);
})->with([
    'canonical' => ['example', [], 'http://localhost/example'],
    'canonical with lang' => ['example', ['lang' => 'it'], 'http://localhost/it/example'],
    'translation' => ['example.it', [], 'http://localhost/it/example'],
    'translation with another lang' => ['example.it', ['lang' => 'es'], 'http://localhost/es/ejemplo'],
    'translation with the fallback lang' => ['example.it', ['lang' => 'en'], 'http://localhost/example'],
    'translation with an unsupported lang' => ['example.it', ['lang' => 'de'], 'http://localhost/it/example'],
]);

it('generates the url for the application locale unless the route is a translation', function () {
    Route::get('/example', Controller::class)
        ->name('example')
        ->lang(['es', 'it']);

    app(L10n::class)->registerLocalizedRoutes();

    app()->setLocale('es');

    expect(route('example'))
        ->toBe('http://localhost/es/ejemplo')
        ->and(action(Controller::class))
        ->toBe('http://localhost/es/ejemplo')
        ->and(route('example.it'))
        ->toBe('http://localhost/it/example');
});

it('generates localized uri via helpers with scalar parameters', function () {
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
    config(['l10n.route_strategy' => $strategy]);

    Route::get('/posts/{post:slug}', fn () => 'Hello, World!')
        ->name('posts.show')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

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
    Route::domain('example.com')->lang(['es', 'it'])->group(function () {
        Route::get('/example', fn () => 'Hello, World!');
    });

    app(L10n::class)->registerLocalizedRoutes();

    get('http://es.example.com/ejemplo')->assertOk();
    get('http://example.com/it/example')->assertOk();
});

it('translates domains without adding route prefixes', function () {
    config(['l10n.route_strategy' => 'no_prefix']);

    Route::domain('example.com')->lang(['es'])->group(function () {
        Route::get('/example', fn () => 'Hello, World!')->name('example');
    });

    app(L10n::class)->registerLocalizedRoutes();

    get('http://example.com/example')->assertOk();
    get('http://es.example.com/ejemplo')->assertOk();
});

it('prefixes the fallback locale when other locales translate the domain', function () {
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

    get('es/ejemplo')->assertOk();

    expect($matches)->toBeTrue();
})->with([
    'RouteCollection' => [false],
    'CompiledRouteCollection' => [true],
]);

it('registers localized routes idempotently', function (string $strategy) {
    config(['l10n.route_strategy' => $strategy]);

    $route = Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetLocale::class)
        ->lang(['es'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    $count = count(Route::getRoutes()->getRoutes());
    $uri = $route->uri();

    app(L10n::class)->registerLocalizedRoutes();

    expect(Route::getRoutes()->getRoutes())
        ->toHaveCount($count)
        ->and($route->uri())
        ->toBe($uri);
})->with([
    'no prefix' => ['no_prefix'],
    'prefix' => ['prefix'],
    'prefix except default' => ['prefix_except_default'],
]);

test('locale() returns the locale served by the route', function () {
    $canonical = Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    $plain = Route::get('/plain', fn () => 'Hello, World!');

    app(L10n::class)->registerLocalizedRoutes();

    /** @var RouteCollection $routes */
    $routes = Route::getRoutes();

    expect($canonical->locale())
        ->toBe('en')
        ->and($routes->getByName('example.es')->locale())
        ->toBe('es')
        ->and($plain->locale())
        ->toBe('en');
});

test('getTranslations() returns every translation from any localized route', function () {
    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    $canonical = Route::getRoutes()->getByName('example');
    $translated = Route::getRoutes()->getByName('example.es');

    expect($canonical->getTranslations())
        ->toBe(['en' => $canonical, 'es' => $translated])
        ->and($translated->getTranslations())
        ->toBe(['en' => $canonical, 'es' => $translated]);
});

test('getTranslations() filters the translations by locale in the given order', function () {
    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    $canonical = Route::getRoutes()->getByName('example');
    $translated = Route::getRoutes()->getByName('example.es');

    expect($translated->getTranslations('en'))
        ->toBe(['en' => $canonical])
        ->and($translated->getTranslations('es'))
        ->toBe(['es' => $translated])
        ->and($translated->getTranslations('es', 'de', 'en'))
        ->toBe(['es' => $translated, 'en' => $canonical])
        ->and($translated->getTranslations('de'))
        ->toBe([]);
});

test('getTranslations() returns only the fallback locale for a route without translations', function () {
    $plain = Route::get('/plain', fn () => 'Hello, World!');

    app(L10n::class)->registerLocalizedRoutes();

    expect($plain->getTranslations())
        ->toBe(['en' => $plain])
        ->and($plain->getTranslations('es'))
        ->toBe([]);
});

test('getTranslations() resolves the registered translations with cached routes', function () {
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

test('translated route inherits the properties of the canonical route', function () {
    /** @var LocalizedRoute $canonical */
    $canonical = Route::get('/example/{id}', fn () => 'Hello, World!')
        ->lang(['it'])
        ->where('id', '[0-9]+')
        ->defaults('id', 1)
        ->withTrashed()
        ->block(30, 5);

    $localized = $canonical->makeTranslation('it');

    expect($localized)
        ->not->toBeNull()
        ->and($localized->wheres)->toBe(['id' => '[0-9]+'])
        ->and($localized->defaults)->toBe(['id' => 1])
        ->and($localized->allowsTrashedBindings())->toBeTrue()
        ->and($localized->locksFor())->toBe(30)
        ->and($localized->waitsFor())->toBe(5);
});

test('translated fallback route is still a fallback route', function () {
    /** @var LocalizedRoute $canonical */
    $canonical = Route::fallback(fn () => 'Fallback')->lang(['it']);

    expect($canonical->makeTranslation('it')?->isFallback)->toBeTrue();
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
