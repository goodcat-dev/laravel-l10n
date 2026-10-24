<?php

namespace Goodcat\L10n\Tests;

use Goodcat\L10n\L10nServiceProvider;
use Illuminate\Translation\Translator;
use Laravel\Wayfinder\WayfinderServiceProvider;
use Tighten\Ziggy\ZiggyServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(Translator::class)->addPath(__DIR__.'/Support/lang');
    }

    protected function getPackageProviders($app): array
    {
        return [
            L10nServiceProvider::class,
            WayfinderServiceProvider::class,
            ZiggyServiceProvider::class,
        ];
    }
}
