<?php

namespace Goodcat\L10n\Tests;

use Goodcat\L10n\LocalizationServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LocalizationServiceProvider::class,
        ];
    }
}
