---
name: l10n-migration
description: Migrate from mcamara/laravel-localization or codezero-be/laravel-localized-routes to goodcat/laravel-l10n.
---

# Migrate to laravel-l10n

## When to use this skill

Use this skill when the user wants to migrate an existing Laravel application from **mcamara/laravel-localization** or **codezero-be/laravel-localized-routes** to **goodcat/laravel-l10n** (already installed).

## Step 1 — Identify source package

Check `composer.json` to determine which package is currently installed:

```bash
grep -E "mcamara/laravel-localization|codezero-be/laravel-localized-routes" composer.json
```

The migration steps below cover both packages. Apply only the sections relevant to the detected source.

## Step 2 — Extract available locales

Extract the list of supported locales from the old package's configuration and store it in `config('app.available_locales')` so it can be referenced throughout the migration.

**From mcamara/laravel-localization** — open `config/laravellocalization.php`:

```php
// Old: supportedLocales array (keys are locale codes)
'supportedLocales' => [
    'en' => ['name' => 'English', ...],
    'es' => ['name' => 'Spanish', ...],
],
```

Extract the locale keys and add them to `config/app.php`:

```php
'available_locales' => ['en', 'es'],
```

**From codezero-be/laravel-localized-routes** — open `config/localized-routes.php`:

```php
// Old
'supported_locales' => ['en', 'es'],
```

Copy the array to `config/app.php`:

```php
'available_locales' => ['en', 'es'],
```

All subsequent steps use `config('app.available_locales')` when calling `->lang()`.

## Step 3 — Migrate configuration

Create or update `config/l10n.php` (publish with `php artisan vendor:publish --tag=l10n-config`):

```php
return [
    'add_locale_prefix' => true,
];
```

**Config mapping — mcamara/laravel-localization:**

| Old key (`laravellocalization.php`) | New key (`l10n.php`) | Notes |
|---|---|---|
| `hideDefaultLocaleInURL` | `add_locale_prefix` | Inverted logic: `hideDefaultLocaleInURL => true` equals `add_locale_prefix => true` (both hide the prefix for the default/fallback locale). When `add_locale_prefix` is `false`, no locale prefix is added to any route. |
| `supportedLocales` | — | Moved to `config('app.available_locales')` (Step 2) |
| `useAcceptLanguageHeader` | — | Handled by `BrowserLocale` resolver (enabled by default via `SetPreferredLocale` middleware) |

All other mcamara config keys (`localesOrder`, `localesMapping`, `utf8suffix`, `urlsIgnored`, etc.) have no direct equivalent. Review each for application-specific needs.

**Config mapping — codezero-be/laravel-localized-routes:**

| Old key (`localized-routes.php`) | New key (`l10n.php`) | Notes |
|---|---|---|
| `omitted_locale` | `add_locale_prefix` | If `omitted_locale` was set to the default locale, set `add_locale_prefix => true` (default). The fallback locale is never prefixed. |
| `supported_locales` | — | Moved to `config('app.available_locales')` (Step 2) |

## Step 4 — Migrate middleware

Remove old middleware registrations and add laravel-l10n middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web([
        \Goodcat\L10n\Middleware\SetLocale::class,
        \Goodcat\L10n\Middleware\SetPreferredLocale::class,
    ]);
})
```

**Middleware mapping — mcamara/laravel-localization:**

| Old | New | Notes |
|---|---|---|
| `\Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes` | `SetLocale` | Route-based locale detection |
| `\Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter` | — | No equivalent; laravel-l10n does not redirect. Handle redirects in application code if needed. |
| `\Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect` | — | Session-based locale is handled by the `SessionLocale` resolver via `SetPreferredLocale` |
| `\Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect` | — | No cookie resolver by default. Create a custom `LocaleResolver` if needed. |

**Middleware mapping — codezero-be/laravel-localized-routes:**

| Old | New | Notes |
|---|---|---|
| `\CodeZero\LocalizedRoutes\Middleware\SetLocale` | `\Goodcat\L10n\Middleware\SetLocale` | Direct replacement |

## Step 5 — Migrate route definitions

**From mcamara/laravel-localization:**

Before:
```php
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect'],
], function () {
    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
});
```

After:
```php
Route::lang(config('app.available_locales'))->group(function () {
    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
});
```

**From codezero-be/laravel-localized-routes:**

Before:
```php
Route::localized(function () {
    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
});
```

After:
```php
Route::lang(config('app.available_locales'))->group(function () {
    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
});
```

Individual routes can also be localized without a group:

```php
Route::get('/about', [AboutController::class, 'index'])
    ->name('about')
    ->lang(config('app.available_locales'));
```

## Step 6 — Migrate route translation files

laravel-l10n uses `lang/{locale}/routes.php` — the same convention as codezero-be. Keys are URIs **without** the leading slash:

```php
// lang/es/routes.php
return [
    'about'   => 'acerca-de',
    'contact' => 'contacto',
];
```

**From mcamara:** translation files are typically in the same `lang/{locale}/routes.php` format with identical key structure. Verify that keys match your route URIs without leading slashes.

**From codezero:** translation files are directly compatible. No changes needed.

If no translation is provided for a given URI/locale pair, laravel-l10n uses the original URI as-is.

## Step 7 — Migrate URL generation

**From mcamara/laravel-localization:**

| Old | New |
|---|---|
| `LaravelLocalization::getLocalizedURL('es', route('about'))` | `route('about', ['lang' => 'es'])` |
| `LaravelLocalization::getLocalizedURL('es')` | `route(Route::currentRouteName(), array_merge(Route::current()->parameters(), ['lang' => 'es']))` |
| `LaravelLocalization::getCurrentLocale()` | `app()->getLocale()` |
| `LaravelLocalization::getSupportedLocales()` | `config('app.available_locales')` |
| `LaravelLocalization::getDefaultLocale()` | `app()->getFallbackLocale()` |

**From codezero-be/laravel-localized-routes:**

| Old | New |
|---|---|
| `Route::localizedUrl('es')` | `route(Route::currentRouteName(), array_merge(Route::current()->parameters(), ['lang' => 'es']))` |
| `route('about.es')` | `route('about', ['lang' => 'es'])` |
| `route('about')` (with locale prefix) | `route('about')` (uses `app()->getLocale()` automatically) |

The `lang` parameter is consumed internally by laravel-l10n's `LocalizedUrlGenerator` and never appears as a query parameter.

For controller-based URL generation:

```php
action(AboutController::class, ['lang' => 'es']);
```

## Step 8 — Migrate Blade templates

**Language switcher:**

Before (mcamara):
```blade
@foreach(LaravelLocalization::getSupportedLocales() as $code => $properties)
    <a href="{{ LaravelLocalization::getLocalizedURL($code) }}"
       hreflang="{{ $code }}">
        {{ $properties['native'] }}
    </a>
@endforeach
```

Before (codezero):
```blade
@foreach(config('localized-routes.supported_locales') as $locale)
    <a href="{{ Route::localizedUrl($locale) }}"
       hreflang="{{ $locale }}">
        {{ $locale }}
    </a>
@endforeach
```

After (laravel-l10n):
```blade
@foreach(config('app.available_locales') as $locale)
    <a href="{{ route(Route::currentRouteName(), array_merge(Route::current()->parameters(), ['lang' => $locale])) }}"
       hreflang="{{ $locale }}">
        {{ $locale }}
    </a>
@endforeach
```

**Hreflang tags:**

```blade
@foreach(config('app.available_locales') as $locale)
    <link rel="alternate" hreflang="{{ $locale }}"
          href="{{ route(Route::currentRouteName(), array_merge(Route::current()->parameters(), ['lang' => $locale])) }}">
@endforeach
```

**Checking current locale in templates:**

| Old | New |
|---|---|
| `LaravelLocalization::getCurrentLocale()` | `app()->getLocale()` |
| `L10n::is('about')` (laravel-l10n) | Matches the current route across all localized variants — use instead of checking route name + locale manually |

## Step 9 — Migrate locale detection

laravel-l10n uses the `SetPreferredLocale` middleware with a chain of resolvers:

| Detection method | Old package mechanism | laravel-l10n equivalent |
|---|---|---|
| URL prefix | Route group prefix (mcamara) / `Route::localized()` (codezero) | `SetLocale` middleware reads `action['locale']` from matched route |
| Session | `LocaleSessionRedirect` middleware (mcamara) | `SessionLocale` resolver — reads `session('locale')` |
| User preference | Manual implementation | `UserLocale` resolver — calls `$user->preferredLocale()` on models implementing `HasLocalePreference` |
| Browser Accept-Language | `useAcceptLanguageHeader` config (mcamara) | `BrowserLocale` resolver — enabled by default |
| Cookie | `LocaleCookieRedirect` middleware (mcamara) | No built-in resolver — create a custom `LocaleResolver` implementation |

**Important difference:** laravel-l10n separates route locale (`SetLocale`) from preferred locale (`SetPreferredLocale`). The route locale determines which translated route variant matched. The preferred locale is the user's detected preference, stored via `app()->setPreferredLocale()` and accessible via `app()->getPreferredLocale()`.

To create a custom resolver (e.g., cookie-based):

```php
use Goodcat\L10n\Resolvers\LocaleResolver;
use Illuminate\Http\Request;

class CookieLocale implements LocaleResolver
{
    public function resolve(Request $request): ?string
    {
        return $request->cookie('locale');
    }
}
```

Register it:

```php
use Goodcat\L10n\L10n;

L10n::$preferredLocaleResolvers = [
    new \App\Resolvers\CookieLocale,
    new \Goodcat\L10n\Resolvers\SessionLocale,
    new \Goodcat\L10n\Resolvers\UserLocale,
    new \Goodcat\L10n\Resolvers\BrowserLocale,
];
```

## Step 10 — Cleanup

1. Remove the old package:

   ```bash
   # For mcamara
   composer remove mcamara/laravel-localization

   # For codezero
   composer remove codezero-be/laravel-localized-routes
   ```

2. Delete old config files:
   - `config/laravellocalization.php` (mcamara)
   - `config/localized-routes.php` (codezero)

3. Remove old middleware registrations from `bootstrap/app.php` or `app/Http/Kernel.php`.

4. Remove old service provider registrations if manually added (both packages support auto-discovery, so this may not apply).

5. Search for remaining references to old package classes:

   ```bash
   # mcamara
   grep -r "LaravelLocalization\|Mcamara" app/ resources/ routes/ config/

   # codezero
   grep -r "CodeZero\|localizedUrl\|Route::localized" app/ resources/ routes/ config/
   ```

6. Run tests to verify everything works after migration.

## Key architectural differences

- **Route-level vs group-level localization**: laravel-l10n attaches locales to individual routes via `->lang()`. Groups are supported via `Route::lang()->group()`, but each route inside can also have its own `->lang()` call. There are no global "localized route group" wrappers.

- **No automatic redirects**: laravel-l10n does not automatically redirect users to their preferred locale URL. If you need redirect behavior, implement it in your application (e.g., a middleware that redirects based on `app()->getPreferredLocale()`).

- **Canonical route preserved**: the original (non-localized) route remains registered with its original name. Localized variants get `.{locale}` appended to the name. `L10n::is('about')` matches all variants.

- **URL generation via `lang` parameter**: instead of helper methods like `getLocalizedURL()` or `localizedUrl()`, pass `['lang' => $locale]` to the standard `route()` and `action()` helpers. The `lang` key is consumed internally.

- **Preferred locale is separate from route locale**: `app()->getLocale()` reflects the matched route's locale (set by `SetLocale`). `app()->getPreferredLocale()` reflects the user's detected preference (set by `SetPreferredLocale`). These may differ.
