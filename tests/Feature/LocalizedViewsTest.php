<?php

use Goodcat\L10n\L10n;
use Illuminate\View\Factory;

it('register localized views path', function () {
    $path = \resource_path('views/' . 'es');

    if (!file_exists($path)) {
        mkdir($path);
    }

    app()->setLocale('es');

    expect(L10n::$localizedViewsPath)->toBe($path);

    /** @var Factory $views */
    $views = \app('view');

    $this->assertContains($path, $views->getFinder()->getPaths());

    rmdir($path);
});

it('replace localized views path', function () {
    $path = \resource_path('views/' . 'es');

    if (!file_exists($path)) {
        mkdir($path);
    }

    app()->setLocale('es');

    $replace = \resource_path('views/' . 'it');

    if (!file_exists($replace)) {
        mkdir($replace);
    }

    app()->setLocale('it');

    expect(L10n::$localizedViewsPath)->toBe($replace);

    /** @var Factory $views */
    $views = \app('view');

    $this->assertContains($replace, $views->getFinder()->getPaths());

    $this->assertNotContains($path, $views->getFinder()->getPaths());

    rmdir($path);

    rmdir($replace);
});