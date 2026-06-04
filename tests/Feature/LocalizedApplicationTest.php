<?php

use Illuminate\View\FileViewFinder;

it('register localized views path', function () {
    $es = resource_path('views/es');
    $it = resource_path('views/it');

    foreach ([$es, $it] as $dir) {
        if (! file_exists($dir)) {
            mkdir($dir);
        }
    }

    app()->setLocale('es');

    app()->setLocale('it');

    /** @var FileViewFinder $finder */
    $finder = app('view')->getFinder();

    expect($finder->getPaths())->toContain($it);
    expect($finder->getPaths())->not->toContain($es);

    foreach ([$es, $it] as $dir) {
        rmdir($dir);
    }
});

it('detects fallback locale', function () {
    app()->setFallbackLocale('fr');

    expect(app()->isFallbackLocale('fr'))->toBeTrue();
});
