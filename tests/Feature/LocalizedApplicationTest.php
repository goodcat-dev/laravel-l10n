<?php

use Illuminate\Support\Facades\File;
use Illuminate\View\FileViewFinder;

it('replaces the localized views path when the locale changes', function () {
    $es = resource_path('views/es');
    $it = resource_path('views/it');

    try {
        foreach ([$es, $it] as $dir) {
            File::makeDirectory($dir);
        }

        app()->setLocale('es');
        app()->setLocale('it');

        /** @var FileViewFinder $finder */
        $finder = app('view')->getFinder();

        expect($finder->getPaths())
            ->toContain($it)
            ->and($finder->getPaths())
            ->not->toContain($es);
    } finally {
        File::deleteDirectory($es);
        File::deleteDirectory($it);
    }
});

it('resolves the same view in the new locale after changing the locale', function () {
    app()->setBasePath(dirname(__DIR__).'/Support');

    app()->setLocale('en');

    $finder = app('view')->getFinder();

    expect($finder->find('greeting'))->toBe(resource_path('views/en/greeting.blade.php'));

    app()->setLocale('it');

    expect($finder->find('greeting'))->toBe(resource_path('views/it/greeting.blade.php'));
});

it('detects fallback locale', function () {
    app()->setFallbackLocale('fr');

    expect(app()->isFallbackLocale('fr'))->toBeTrue();
});
