<?php

use Goodcat\L10n\L10n;
use Goodcat\L10n\Listeners\RegisterLocalizedViewsPath;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

it('register localized views path', function () {
    $path = resource_path('views/es');

    if (! file_exists($path)) {
        mkdir($path);
    }

    app()->setLocale('es');

    $paths = app('view')->getFinder()->getPaths();

    expect($paths)->toContain($path);

    rmdir($path);

    $replace = resource_path('views/it');

    if (! file_exists($replace)) {
        mkdir($replace);
    }

    app()->setLocale('it');

    $paths = app('view')->getFinder()->getPaths();

    expect($paths)
        ->toContain($replace)
        ->not->toContain($path);

    rmdir($replace);
});
