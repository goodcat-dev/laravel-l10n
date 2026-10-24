{{-- goodcat/laravel-l10n – Opinionated Laravel localization package --}}

# laravel-l10n

Route-level localization for Laravel. Locales are declared per-route (or per-group) via `->lang()`, not via route-group wrappers. The package automatically generates translated route variants, handles locale-aware URL generation, preferred locale detection, and localized views.

## Setup

The service provider registers `SetLocale` automatically in the `web` and `api` middleware groups, with priority before `SubstituteBindings`. Routes outside these groups need explicit registration on the route or in their custom group; the package still manages its priority.

To enable optional preferred locale detection, register `SetPreferredLocale` in `bootstrap/app.php`:

@verbatim
<code-snippet name="bootstrap/app.php" lang="php">
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Goodcat\L10n\Middleware\SetPreferredLocale::class,
    ]);
})
</code-snippet>
@endverbatim

- `SetLocale` sets the active application and request locale to the route's language. Canonical routes declared with `lang()` explicitly use `APP_FALLBACK_LOCALE` in every strategy, even if their translations collide. Routes without localization preserve the application's locale.
- `SetPreferredLocale` checks Session, User, then Browser and stores the preferences of the first resolver that detects any, as an ordered list, in `config('app.preferred_locales')` for the current application. It does not persist them to the session or user.

## Route definitions

Attach `->lang()` to individual routes or to a group:

@verbatim
<code-snippet name="routes/web.php" lang="php">
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

After the application boots, the package registers localized variants of routes declared with `lang()`. A route named `example` gets variants such as `example.es` and `example.it`; its canonical route keeps the name `example`. To generate an URL for a specific locale, pass `['lang' => $locale]` to `route('example', ...)` instead of constructing a localized name.

## Route translation files

Provide URI translations in `lang/{locale}/routes.php`. Keys are canonical URIs **without** the leading slash:

@verbatim
<code-snippet name="lang/es/routes.php" lang="php">
return [
    'example' => 'ejemplo',
    'about' => 'acerca-de',
];
</code-snippet>
@endverbatim

If no translation exists for a locale, the original URI is used as-is.

With a custom binding key such as `/article/{post:slug}`, use `article/{post}` as the translation key. Laravel stores `:slug` separately, and the localized route still binds by slug.

## URL generation

Use the standard `route()` and `action()` helpers with a `lang` parameter. The `lang` key selects the locale and is consumed internally; do not name a route placeholder `{lang}`:

@verbatim
<code-snippet name="Generate localized URLs" lang="php">
// By route name
route('example', ['lang' => 'es']); // /es/ejemplo with the default strategy

// By controller action
action(ExampleController::class, ['lang' => 'es']); // /es/ejemplo

// Without lang, uses app()->getLocale() to select a route
route('example');
</code-snippet>
@endverbatim

## Config

@verbatim
<code-snippet name="config/l10n.php" lang="php">
return [
    'route_strategy' => 'prefix_except_default',
];
</code-snippet>
@endverbatim

With `prefix_except_default` (default), the fallback locale keeps the unprefixed canonical URI. `prefix` prefixes the canonical as well; `no_prefix` uses translated URIs without locale prefixes. The fallback locale (`APP_FALLBACK_LOCALE`) must match the language of the canonical routes. With `no_prefix`, avoid translations that collide with other routes' method, domain, and URI.

## Preferred locale detection

`SetPreferredLocale` middleware runs resolvers in order. The first non-null result wins:

1. **SessionLocale** (deprecated) — reads `session('locale')`
2. **UserLocale** — calls `$user->preferredLocale()` if the user model implements `HasLocalePreference`
3. **BrowserLocale** — parses `Accept-Language` header and returns all its languages in order of preference

`SessionLocale` remains in the default chain for compatibility. For session-based preferences, implement a custom `LocaleResolver` matching the session key written by the application. Neither Laravel nor this package automatically writes `session('locale')`.

Override the resolver chain:

@verbatim
<code-snippet name="Customize preferred locale resolvers" lang="php">
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
<code-snippet name="Application and route helpers" lang="php">
app()->getPreferredLocales();  // ?array — the detected preferred locales, in order of preference
app()->getPreferredLocale();   // ?string — the first preferred locale
app()->getPreferredLocale(['en', 'es']); // ?string — best match among the given locales (es_ES matches es_ES, then es, then es_MX); null if none
app()->setPreferredLocale($locale); // accepts a locale or a list, dispatches PreferredLocaleUpdated event (see below)
app()->isFallbackLocale('en'); // bool — true if 'en' is the fallback locale

L10n::is('example');           // bool — like Route::is() but matches across all localized variants
L10n::is('admin.*');           // supports wildcard patterns (delegates to Route::named())
</code-snippet>
@endverbatim

## Events

`Goodcat\L10n\Events\PreferredLocaleUpdated` is dispatched whenever `app()->setPreferredLocale()` is called. It exposes `$locales` and `$previousLocales` as public properties:

@verbatim
<code-snippet name="Listen for preferred locale updates" lang="php">
use Goodcat\L10n\Events\PreferredLocaleUpdated;

Event::listen(PreferredLocaleUpdated::class, function (PreferredLocaleUpdated $event) {
    // $event->locales — the new preferred locales
    // $event->previousLocales — the previous preferred locales (nullable)
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
<code-snippet name="Ziggy usage" lang="js">
import { route } from '@/l10n';
route('example', { id: 1, lang: 'es' });
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Wayfinder usage" lang="ts">
import { route } from '@/l10n';
import example from '@/routes/example';
route(example, { lang: 'es' }).url;
</code-snippet>
@endverbatim

Both helpers resolve the locale from an explicit `lang` parameter, then `document.documentElement.lang`, and otherwise use the canonical route. During server-side rendering, pass `lang` explicitly to select a localized route. The Wayfinder helper supports named routes, not actions.
