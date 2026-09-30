<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\RedirectToPreferredLocale;
use Goodcat\L10n\Middleware\SetPreferredLocale;
use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\SessionLocale;
use Goodcat\L10n\Resolvers\UserLocale;
use Goodcat\L10n\Tests\Support\User;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withHeader;
use function Pest\Laravel\withSession;

it('has default resolvers', function () {
    $resolvers = L10n::getPreferredLocaleResolvers();

    expect($resolvers)->toMatchArray([
        new SessionLocale,
        new UserLocale,
        new BrowserLocale,
    ]);
});

it('detects preferred locale from browser', function () {
    L10n::$preferredLocaleResolvers = [new BrowserLocale];

    Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetPreferredLocale::class);

    withHeader('Accept-Language', 'es')->get('/example');

    expect(app()->getPreferredLocale())->toBe('es');
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
})->with([null, 'de']);

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
