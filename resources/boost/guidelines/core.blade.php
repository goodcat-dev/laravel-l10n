{{-- goodcat/laravel-l10n – Opinionated Laravel localization package --}}

# laravel-l10n

Route-level localization for Laravel. Locales are declared per-route (or per-group) via `->lang()`, not via route-group wrappers. The package automatically generates translated route variants, handles locale-aware URL generation, preferred locale detection, and localized views.

## Setup

Register middleware in `bootstrap/app.php`:

@verbatim
<code-snippet title="bootstrap/app.php">
->withMiddleware(function (Middleware $middleware) {
    $middleware->web([
        \Goodcat\L10n\Middleware\SetLocale::class,
        \Goodcat\L10n\Middleware\SetPreferredLocale::class,
    ]);
})
</code-snippet>
@endverbatim

- `SetLocale` reads the locale from the matched route and calls `app()->setLocale()`.
- `SetPreferredLocale` runs resolvers (Session → User → Browser) to detect and store the user's preferred locale.

## Route definitions

Attach `->lang()` to individual routes or to a group:

@verbatim
<code-snippet title="routes/web.php">
// Single route
Route::get('/example', ExampleController::class)
    ->name('example')
    ->lang(['es', 'it']);

// Group
Route::lang(['es', 'it'])->group(function () {
    Route::get('/about', AboutController::class)->name('about');
    Route::get('/contact', ContactController::class)->name('contact');
});
</code-snippet>
@endverbatim

On boot, `L10n::registerLocalizedRoutes()` generates a localized copy for each locale. A route named `example` gets variants `example.es`, `example.it`, etc. The canonical route keeps its original name. You never need to use the `.{locale}` suffix directly — pass `['lang' => $locale]` to `route()` and the `LocalizedUrlGenerator` resolves the correct variant internally.

## Route translation files

Provide URI translations in `lang/{locale}/routes.php`. Keys are URIs **without** the leading slash:

@verbatim
<code-snippet title="lang/es/routes.php">
return [
    'example' => 'ejemplo',
    'about'   => 'acerca-de',
];
</code-snippet>
@endverbatim

If no translation exists for a locale, the original URI is used as-is.

## URL generation

Use the standard `route()` and `action()` helpers with a `lang` parameter. The `lang` key is consumed internally and never appears as a query parameter:

@verbatim
<code-snippet>
// By route name
route('example', ['lang' => 'es']);          // /es/ejemplo

// By controller action
action(ExampleController::class, ['lang' => 'es']); // /es/ejemplo

// Without lang — uses app()->getLocale()
route('example');                            // /example (fallback locale)
</code-snippet>
@endverbatim

## Config

@verbatim
<code-snippet title="config/l10n.php">
return [
    'add_locale_prefix' => true,
];
</code-snippet>
@endverbatim

When `true` (default), localized routes are prefixed with the locale code (e.g. `/es/ejemplo`). The fallback locale is never prefixed. Set to `false` to rely solely on translated URIs without prefixes.

## Preferred locale detection

`SetPreferredLocale` middleware runs resolvers in order. The first non-null result wins:

1. **SessionLocale** — reads `session('locale')`
2. **UserLocale** — calls `$user->preferredLocale()` if the user model implements `HasLocalePreference`
3. **BrowserLocale** — parses `Accept-Language` header

Override the resolver chain:

@verbatim
<code-snippet>
use Goodcat\L10n\L10n;
use Goodcat\L10n\Resolvers\BrowserLocale;

L10n::$preferredLocaleResolvers = [
    new MyCustomResolver,
    new BrowserLocale,
];
</code-snippet>
@endverbatim

## Helpers

@verbatim
<code-snippet>
app()->getPreferredLocale();   // ?string — the detected preferred locale
app()->setPreferredLocale($locale); // sets preferred locale, dispatches PreferredLocaleUpdated event (see below)
app()->isFallbackLocale('en'); // bool — true if 'en' is the fallback locale

L10n::is('example');           // bool — like Route::is() but matches across all localized variants
L10n::is('admin.*');           // supports wildcard patterns (delegates to Route::named())
</code-snippet>
@endverbatim

## Events

`Goodcat\L10n\Events\PreferredLocaleUpdated` is dispatched whenever `app()->setPreferredLocale()` is called. It exposes `$locale` and `$previousLocale` as public properties:

@verbatim
<code-snippet>
use Goodcat\L10n\Events\PreferredLocaleUpdated;

Event::listen(PreferredLocaleUpdated::class, function (PreferredLocaleUpdated $event) {
    // $event->locale — the new preferred locale
    // $event->previousLocale — the previous preferred locale (nullable)
});
</code-snippet>
@endverbatim

## Localized views

When the locale changes, `RegisterLocalizedViewsPath` prepends `resources/views/{locale}/` to the view finder's path list. Place locale-specific views in subdirectories:

```
resources/views/
├── example.blade.php          ← fallback
├── es/
│   └── example.blade.php      ← Spanish
└── it/
    └── example.blade.php      ← Italian
```

The directory must exist on disk; otherwise the step is silently skipped.

## JavaScript integration

Publish stubs for client-side localized URL generation:

- **Ziggy**: `php artisan vendor:publish --tag=l10n-ziggy` → `resources/js/l10n.js`
- **Wayfinder**: `php artisan vendor:publish --tag=l10n-wayfinder` → `resources/js/l10n.ts`

@verbatim
<code-snippet title="Ziggy usage">
import { route } from '@/l10n';
route('example', { id: 1, lang: 'es' });
</code-snippet>
@endverbatim

@verbatim
<code-snippet title="Wayfinder usage">
import { route } from '@/l10n';
import example from '@/routes/example';
route(example, { lang: 'es' }).url;
</code-snippet>
@endverbatim

Both stubs resolve locale from `params.lang`, then `document.documentElement.lang`, then fallback. Only named routes are supported with Wayfinder.
