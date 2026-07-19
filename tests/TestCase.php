<?php

namespace Goodcat\L10n\Tests;

use Goodcat\L10n\L10nServiceProvider;
use Laravel\Wayfinder\WayfinderServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            L10nServiceProvider::class,
            WayfinderServiceProvider::class,
        ];
    }
}
