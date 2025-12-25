<?php

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
