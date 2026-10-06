<?php

use Goodcat\L10n\Events\PreferredLocaleUpdated;
use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\RedirectToPreferredLocale;
use Goodcat\L10n\Middleware\SetPreferredLocale;
use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\SessionLocale;
use Goodcat\L10n\Resolvers\UserLocale;
use Goodcat\L10n\Tests\Support\User;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withHeader;
use function Pest\Laravel\withSession;

beforeEach(fn () => L10n::$preferredLocaleResolvers = []);

it('detects preferred locale from browser', function () {
    L10n::$preferredLocaleResolvers = [new BrowserLocale];

    Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetPreferredLocale::class);

    withHeader('Accept-Language', 'es-ES,es;q=0.9,en;q=0.8')->get('/example');

    expect(app()->getPreferredLocale())
        ->toBe('es_ES')
        ->and(app()->getPreferredLocales())
        ->toBe(['es_ES', 'es', 'en']);
});

it('ignores the wildcard and duplicated browser languages', function () {
    expect((new BrowserLocale)->resolve(request()->duplicate(server: [
        'HTTP_ACCEPT_LANGUAGE' => '*,es-ES;q=0.9,es-ES;q=0.8,en;q=0.7',
    ])))->toBe(['es_ES', 'en']);
});

it('stores a single preferred locale as a list', function () {
    expect(app()->getPreferredLocale())->toBeNull()
        ->and(app()->getPreferredLocales())->toBeNull();

    app()->setPreferredLocale('fr');

    expect(app()->getPreferredLocale())->toBe('fr')
        ->and(app()->getPreferredLocales())->toBe(['fr']);
});

it('dispatches the preferred locales with the previous ones', function () {
    Event::fake([PreferredLocaleUpdated::class]);

    app()->setPreferredLocale('fr');
    app()->setPreferredLocale(['es_ES', 'en']);

    Event::assertDispatched(fn (PreferredLocaleUpdated $event) => $event->locales === ['es_ES', 'en']
        && $event->previousLocales === ['fr']);
});

it('matches the preferred locale against the given locales', function () {
    app()->setPreferredLocale(['es_ES', 'en']);

    expect(app()->getPreferredLocale(['en', 'es_ES']))->toBe('es_ES')
        ->and(app()->getPreferredLocale(['en', 'es']))->toBe('es')
        ->and(app()->getPreferredLocale(['es_MX', 'fr']))->toBe('es_MX')
        ->and(app()->getPreferredLocale(['es_MX', 'es']))->toBe('es')
        ->and(app()->getPreferredLocale(['en', 'es_MX']))->toBe('es_MX')
        ->and(app()->getPreferredLocale(['de', 'fr']))->toBeNull();
});

it('detects preferred locale from user', function (Authenticatable $user) {
    L10n::$preferredLocaleResolvers = [new UserLocale];

    Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetPreferredLocale::class);

    actingAs($user)->get('/example');

    $expected = $user instanceof User ? 'en' : null;

    expect(app()->getPreferredLocale())->toBe($expected);
})->with([
    'withPreferredLocale' => new User,
    'withoutPreferredLocale' => new Authenticatable,
]);

it('detects preferred locale from the session', function () {
    L10n::$preferredLocaleResolvers = [new SessionLocale];

    Route::get('/example', fn () => 'Hello, World!')
        ->middleware([StartSession::class, SetPreferredLocale::class]);

    withSession(['locale' => 'fr'])->get('/example');

    expect(app()->getPreferredLocale())->toBe('fr');
});

it('checks the preference once and allows switching afterwards', function (string $path, bool $redirects) {
    L10n::$preferredLocaleResolvers = [new BrowserLocale];

    Route::get('/example/{id}', fn () => 'Page')
        ->middleware([StartSession::class, SetPreferredLocale::class, RedirectToPreferredLocale::class])
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    withHeader('Accept-Language', 'es');

    $response = get($path);

    $redirects
        ? $response->assertRedirect('http://localhost/es/example/42?page=2')
        : $response->assertOk();

    $response->assertSessionHas('l10n.redirected_to_preferred_locale', true);

    get('/example/42')->assertOk();
})->with([
    'default locale' => ['/example/42?page=2', true],
    'preferred locale' => ['/es/example/42', false],
]);

it('redirects to the closest translation of a regional preference', function (string $header, string $name) {
    L10n::$preferredLocaleResolvers = [new BrowserLocale];

    Route::get('/example/{id}', fn () => 'Page')
        ->middleware([StartSession::class, SetPreferredLocale::class, RedirectToPreferredLocale::class])
        ->lang(['es', 'pt_BR', 'zh'])
        ->name('example');

    app(L10n::class)->registerLocalizedRoutes();

    withHeader('Accept-Language', $header);

    get('/example/42')->assertRedirect(route($name, ['id' => 42]));
})->with([
    'region falls back to language' => ['es-ES,es;q=0.9', 'example.es'],
    'exact regional match' => ['pt-BR', 'example.pt_BR'],
    'script and region fall back to language' => ['zh-Hant-TW', 'example.zh'],
    'unsupported first choice' => ['de-DE,de;q=0.9,pt-BR;q=0.8', 'example.pt_BR'],
]);

it('skips redirecting when no preferred translation is available', function (?string $locale) {
    L10n::$preferredLocaleResolvers = [new BrowserLocale];

    Route::get('/example/{id}', fn () => 'Page')
        ->middleware([StartSession::class, SetPreferredLocale::class, RedirectToPreferredLocale::class])
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    if ($locale) {
        withHeader('Accept-Language', $locale);
    }

    get('/example/42')
        ->assertOk()
        ->assertSessionHas('l10n.redirected_to_preferred_locale', true);
})->with([
    'no preference' => [null],
    'unsupported preference' => ['de'],
]);

it('ignores routes without translations', function () {
    L10n::$preferredLocaleResolvers = [new BrowserLocale];

    Route::get('/about', fn () => 'About')
        ->middleware([StartSession::class, SetPreferredLocale::class, RedirectToPreferredLocale::class]);

    withHeader('Accept-Language', 'es');

    get('/about')->assertOk()->assertSessionMissing('l10n.redirected_to_preferred_locale');
});

it('lets post requests reach the controller', function () {
    L10n::$preferredLocaleResolvers = [new BrowserLocale];

    Route::post('/example/{id}', fn () => 'Saved')
        ->middleware([StartSession::class, SetPreferredLocale::class, RedirectToPreferredLocale::class])
        ->lang(['es']);

    app(L10n::class)->registerLocalizedRoutes();

    withHeader('Accept-Language', 'es');

    post('/example/42')
        ->assertOk()
        ->assertContent('Saved')
        ->assertSessionMissing('l10n.redirected_to_preferred_locale');
});
