<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\SetPreferredLocale;
use Goodcat\L10n\Resolvers\BrowserPreferredLocale;
use Goodcat\L10n\Resolvers\UserPreferredLocale;
use Goodcat\L10n\Tests\Support\User;
use Illuminate\Support\Facades\Route;

it('has default resolvers', function () {
    $resolvers = L10n::getPreferredLocaleResolvers();

    expect($resolvers)->toMatchArray([
        new UserPreferredLocale,
        new BrowserPreferredLocale,
    ]);
});

it('detects preferred locale from browser', function () {
    L10n::$preferredLocaleResolvers = [new BrowserPreferredLocale];

    Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetPreferredLocale::class);

    $this->withHeader('Accept-Language', 'en')->get('/example');

    expect(app()->getPreferredLocale())->toBe('en');
});

it('detects preferred locale from user', function () {
    L10n::$preferredLocaleResolvers = [new UserPreferredLocale];

    Route::get('/example', fn () => 'Hello, World!')
        ->middleware(SetPreferredLocale::class);

    $this->actingAs(new User)->get('/example');

    expect(app()->getPreferredLocale())->toBe('en');
});
