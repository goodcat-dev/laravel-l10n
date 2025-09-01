<?php

namespace Goodcat\L10n\Tests\Support;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasLocalePreference
{
    public function preferredLocale(): string
    {
        return 'en';
    }
}
