<?php

namespace Goodcat\L10n\Tests\Support;

use Goodcat\L10n\Contracts\HasLocalizedRouteKey;
use Illuminate\Database\Eloquent\Model;

class Post extends Model implements HasLocalizedRouteKey
{
    protected $guarded = [];

    protected $casts = ['slug' => 'array'];

    public function getLocalizedRouteKey(string $locale, ?string $field = null): ?string
    {
        return $this->{$field ?? $this->getRouteKeyName()}[$locale] ?? null;
    }
}
