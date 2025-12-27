<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\SetPreferredLocale;
use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\SessionLocale;
use Goodcat\L10n\Resolvers\UserLocale;
use Goodcat\L10n\Tests\Support\User;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

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

    $this->withHeader('Accept-Language', 'es')->get('/example');

    expect(app()->getPreferredLocale())->toBe('es');
});

it('detects preferred locale from user', function (Authenticatable $user) {
    L10n::$preferredLocaleResolvers = [new UserLocale];

    Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetPreferredLocale::class);

    $this->actingAs($user)->get('/example');

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

    $this->withSession(['locale' => 'fr'])->get('/example');

    expect(app()->getPreferredLocale())->toBe('fr');
});
