<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Middleware\DetectPreferredLocale;
use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\UserPreferredLocale;
use Goodcat\L10n\Tests\Support\User;
use Illuminate\Support\Facades\Route;

it('has default resolvers', function () {
    $resolvers = L10n::getPreferredLocaleResolvers();

    expect($resolvers)->toMatchArray([
        new UserPreferredLocale,
        new BrowserLocale,
    ]);
});

it('detects preferred locale from browser', function () {
    L10n::$preferredLocaleResolvers = [new BrowserLocale];

    Route::get('{lang}/example', fn () => 'Hello, World!')
        ->lang(['en'])
        ->middleware(DetectPreferredLocale::class);

    $this->withHeader('Accept-Language', 'en')->get('en/example');

    expect(app()->getPreferredLocale())->toBe('en');
});

it('detects preferred locale from user', function () {
    L10n::$preferredLocaleResolvers = [new UserPreferredLocale];

    Route::get('{lang}/example', fn () => 'Hello, World!')
        ->lang(['en'])
        ->middleware(DetectPreferredLocale::class);

    $this->actingAs(new User)->get('en/example');

    expect(app()->getPreferredLocale())->toBe('en');
});
