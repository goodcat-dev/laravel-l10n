<?php

use Goodcat\L10n\L10n;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Translation\Translator;
use Tighten\Ziggy\Ziggy;

beforeEach(fn () => Ziggy::clearRoutes());

it('generates localized routes with Ziggy', function (
    string $strategy,
    string $canonicalUri,
    string $localizedUri,
) {
    app(Translator::class)->addPath(__DIR__.'/../Support/lang');

    config(['l10n.route_strategy' => $strategy]);

    Route::get('/example', fn () => 'Hello, World!')
        ->name('example')
        ->lang(['en', 'es']);

    app(L10n::class)->registerLocalizedRoutes();

    $path = 'storage/framework/testing/ziggy-'.bin2hex(random_bytes(8)).'.js';

    try {
        Artisan::call('ziggy:generate', ['path' => $path]);

        $generated = File::get(base_path($path));

        preg_match('/^const Ziggy = (.+);$/m', $generated, $matches);

        $config = json_decode($matches[1] ?? '', true, flags: JSON_THROW_ON_ERROR);

        expect($generated)
            ->toContain('export { Ziggy };')
            ->and($config['routes'])
            ->toHaveKeys(['example', 'example.es'])
            ->and(array_key_exists('example.en', $config['routes']))
            ->toBeFalse()
            ->and($config['routes']['example']['uri'])
            ->toBe($canonicalUri)
            ->and($config['routes']['example.es']['uri'])
            ->toBe($localizedUri);
    } finally {
        File::delete(base_path($path));
    }
})->with([
    'no prefix' => ['no_prefix', 'example', 'ejemplo'],
    'prefix' => ['prefix', 'en/example', 'es/ejemplo'],
    'prefix except default' => ['prefix_except_default', 'example', 'es/ejemplo'],
]);
