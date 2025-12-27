<?php

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

    expect(app('view')->getFinder()->getPaths())
        ->toContain($it)
        ->not->toContain($es);

    foreach ([$es, $it] as $dir) {
        rmdir($dir);
    }
});

it('detects fallback locale', function () {
    app()->setFallbackLocale('fr');

    expect(app()->isFallbackLocale('fr'))->toBeTrue();
});
