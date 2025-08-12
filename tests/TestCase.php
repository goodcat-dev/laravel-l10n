<?php

namespace Goodcat\L10n\Tests;

use Goodcat\L10n\L10nServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            L10nServiceProvider::class,
        ];
    }
}
