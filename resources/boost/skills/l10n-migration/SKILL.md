---
name: l10n-migration
description: Migrate from mcamara/laravel-localization to goodcat/laravel-l10n.
---

# Migrate to laravel-l10n

## When to use this skill

Use this skill when the user wants to migrate an existing Laravel application from **mcamara/laravel-localization** to **goodcat/laravel-l10n** (already installed).

## Step 1 — Verify source package

Check `composer.json` to confirm mcamara/laravel-localization is installed:

```bash
grep "mcamara/laravel-localization" composer.json
```

## Step 2 — Scan the codebase (discovery)

Before migrating, scan the user project to understand how mcamara is actually used. This informs which optional migrations are needed.

```bash
rg "LaravelLocalization|mcamara/laravel-localization|transRoute|getLocalizedURL|getSupportedLocales|getCurrentLocale|getDefaultLocale" app/ resources/ routes/ config/
rg "LaravelLocalizationRoutes|localizationRedirect|localeSessionRedirect|localeCookieRedirect" app/ bootstrap/ routes/ config/
rg "supportedLocales|hideDefaultLocaleInURL|useAcceptLanguageHeader|localesOrder|localesMapping|urlsIgnored" config/
```

Optional checks:
- If you see `getLocalizedURL()` or `localizationRedirect`, the app likely relied on automatic redirects.
- If you see `LocaleCookieRedirect`, the app likely relied on cookie-based locale detection.
- If you see `transRoute()` or `routes.*` keys, route translation files must be migrated.
- If you see domain-based routing, check for domain translations in `lang/*/routes.php`.

Proceed with the steps below, but only apply optional migrations when the scan indicates they exist.

## Step 3 — Extract available locales

Extract the list of supported locales from the old package's configuration. laravel-l10n does not read locales from config — they are passed directly to `->lang()`. Storing them in `config('app.available_locales')` is a recommended convention to avoid repetition, but any array source works.

Open `config/laravellocalization.php`:

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

All subsequent steps use `$availableLocales` (see Step 5) when calling `->lang()`.

If the app used locale metadata (native names, RTL, etc.) from `supportedLocales`, create a separate config (e.g. `config/app.php` or `config/locales.php`) and keep the metadata there. laravel-l10n only needs the locale codes.

## Step 4 — Migrate configuration

Create or update `config/l10n.php` (publish with `php artisan vendor:publish --tag=l10n-config`):

```php
return [
    'add_locale_prefix' => true,
];
```

**Config mapping:**

| Old key (`laravellocalization.php`) | New key (`l10n.php`) | Notes |
|---|---|---|
| `hideDefaultLocaleInURL` | `add_locale_prefix` | When `hideDefaultLocaleInURL` was `true`, set `add_locale_prefix => true` (default). Both hide the prefix for the default/fallback locale. **Note:** if `hideDefaultLocaleInURL` was `false` (prefix shown for all locales including default), there is no direct equivalent — laravel-l10n never prefixes the fallback locale. When `add_locale_prefix` is `false`, no locale prefix is added to *any* route. |
| `supportedLocales` | — | Moved to `config('app.available_locales')` (Step 2) |
| `useAcceptLanguageHeader` | — | Handled by `BrowserLocale` resolver (enabled by default via `SetPreferredLocale` middleware). Note: this sets the **preferred** locale only; it does not change the current route locale or redirect. |

All other mcamara config keys (`localesOrder`, `localesMapping`, `utf8suffix`, `urlsIgnored`, etc.) have no direct equivalent. Review each for application-specific needs.

## Step 5 — Migrate middleware

Remove old middleware registrations and add laravel-l10n middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web([
        \Goodcat\L10n\Middleware\SetLocale::class,
        \Goodcat\L10n\Middleware\SetPreferredLocale::class,
    ]);
})
```

**Middleware mapping:**

| Old | New | Notes |
|---|---|---|
| `\Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes` | `SetLocale` | Route-based locale detection |
| `\Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter` | — | No equivalent; laravel-l10n does not redirect. If the scan shows this middleware was used, add an application redirect middleware based on `app()->getPreferredLocale()` and `Route::current()->canonical()`. |
| `\Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect` | — | Session-based locale is handled by the `SessionLocale` resolver via `SetPreferredLocale`. This does **not** redirect. Add a redirect middleware if the old behavior is required. |
| `\Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect` | — | No cookie resolver by default. If the scan shows cookie usage, create a custom `LocaleResolver` and optionally a redirect middleware. |

## Step 6 — Migrate route definitions

**Recommendation:** usually only GET routes should be localized with `->lang()` for SEO and clean URLs. POST, PUT, PATCH, DELETE routes do not need translated URIs — they receive the locale via the request context (set by `SetLocale` middleware) and do not appear in browser address bars or search engine indexes.

Before:
```php
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect'],
], function () {
    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
});
```

After:
```php
$availableLocales = config('app.available_locales');

Route::lang($availableLocales)->group(function () {
    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
});

// Non-GET routes stay outside the localized group
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
```

Individual routes can also be localized without a group:

```php
$availableLocales = config('app.available_locales');

Route::get('/about', [AboutController::class, 'index'])
    ->name('about')
    ->lang($availableLocales);
```

`Route::lang()->group()` is supported, and a route can extend the group's locales with its own `->lang()` call — the locales are merged.

## Step 7
— Migrate route translation files

laravel-l10n uses `lang/{locale}/routes.php`. Keys are URIs **without** the leading slash:

```php
// lang/es/routes.php
return [
    'about'   => 'acerca-de',
    'contact' => 'contacto',
];
```

mcamara translation files are typically in the same `lang/{locale}/routes.php` format, but the structure is different: in mcamara the **key** is a translation key (e.g. `routes.about`) and the **value** is the actual URI segment (e.g. `about`, `article/{article}`), which is what `LaravelLocalization::transRoute()` returns. In laravel-l10n, the **key** is the canonical URI (without leading slash), and the **value** is the localized URI for that locale.

To migrate safely:
1. Take the **value** for each `routes.*` key in the fallback locale (usually `en`) and use that value as the canonical URI in your route definition.
2. In each `lang/{locale}/routes.php` file for laravel-l10n, map that canonical URI to the localized URI.

Example migration:

mcamara:
```php
// lang/en/routes.php
return [
    'routes.about' => 'about',
    'routes.article' => 'article/{article}',
];

// lang/es/routes.php
return [
    'routes.about' => 'acerca',
    'routes.article' => 'articulo/{article}',
];

// routes/web.php
Route::group(['prefix' => LaravelLocalization::setLocale()], function () {
    Route::get(LaravelLocalization::transRoute('routes.about'), AboutController::class)
        ->name('about');
    Route::get(LaravelLocalization::transRoute('routes.article'), ArticleController::class)
        ->name('article');
});
```

laravel-l10n:
```php
// routes/web.php
$availableLocales = config('app.available_locales');

Route::lang($availableLocales)->group(function () {
    Route::get('/about', AboutController::class)->name('about');
    Route::get('/article/{article}', ArticleController::class)->name('article');
});

// lang/en/routes.php
return [
    'about' => 'about',
    'article/{article}' => 'article/{article}',
];

// lang/es/routes.php
return [
    'about' => 'acerca',
    'article/{article}' => 'articulo/{article}',
];
```

If no translation is provided for a given URI/locale pair, laravel-l10n uses the original URI as-is.

If the old project used domain-based routing, you can also translate domains in the same `routes.php` files, using the original domain string as the key.

## Step 8 — Migrate URL generation

**Important:** laravel-l10n's `route()` helper expects the **canonical** route name (e.g., `about`), not the localized variant (e.g., `about.es`). Use `Route::current()->canonical()->getName()` to get the canonical name — `canonical()` returns the original route (or `$this` if the route is already canonical).

| Old | New |
|---|---|
| `LaravelLocalization::getLocalizedURL('es', route('about'))` | `route('about', ['lang' => 'es'])` |
| `LaravelLocalization::getLocalizedURL('es')` | `route(Route::current()->canonical()->getName(), array_merge(Route::current()->parameters(), ['lang' => 'es']))` |
| `LaravelLocalization::getCurrentLocale()` | `app()->getLocale()` |
| `LaravelLocalization::getSupportedLocales()` | `config('app.available_locales')` |
| `LaravelLocalization::getDefaultLocale()` | `app()->getFallbackLocale()` |

The `lang` parameter is consumed internally by laravel-l10n's `LocalizedUrlGenerator` and never appears as a query parameter.

For controller-based URL generation:

```php
action(AboutController::class, ['lang' => 'es']);
```

If you need to build a language switcher, ensure the current route has a name; otherwise add a fallback (e.g., to a homepage route).

## Step 9 — Migrate Blade templates

**Language switcher:**

Before:
```blade
@foreach(LaravelLocalization::getSupportedLocales() as $code => $properties)
    <a href="{{ LaravelLocalization::getLocalizedURL($code) }}"
       hreflang="{{ $code }}">
        {{ $properties['native'] }}
    </a>
@endforeach
```

After:
```blade
@foreach(config('app.available_locales') as $locale)
    <a href="{{ route(Route::current()->canonical()->getName(), array_merge(Route::current()->parameters(), ['lang' => $locale])) }}"
       hreflang="{{ $locale }}">
        {{ $locale }}
    </a>
@endforeach
```

**Hreflang tags:**

```blade
@foreach(config('app.available_locales') as $locale)
    <link rel="alternate" hreflang="{{ $locale }}"
          href="{{ route(Route::current()->canonical()->getName(), array_merge(Route::current()->parameters(), ['lang' => $locale])) }}">
@endforeach
```

**Checking current locale in templates:**

| Old | New |
|---|---|
| `LaravelLocalization::getCurrentLocale()` | `app()->getLocale()` |
| `L10n::is('about')` (laravel-l10n) | Matches the current route across all localized variants — use instead of checking route name + locale manually |

If the app used locale metadata (native names, RTL, etc.), render those from your own config rather than the locale code.

## Step 10 — Cleanup

1. Remove the old package:

   ```bash
   composer remove mcamara/laravel-localization
   ```

2. Delete old config file: `config/laravellocalization.php`.

3. Remove old middleware registrations from `bootstrap/app.php` or `app/Http/Kernel.php`.

4. Remove old service provider registrations if manually added (mcamara supports auto-discovery, so this may not apply).

5. Search for remaining references to old package classes:

   ```bash
   grep -r "LaravelLocalization\|Mcamara" app/ resources/ routes/ config/
   ```

6. Run tests to verify everything works after migration.

## Key architectural differences

- **Localized routes are explicit**: only routes tagged with `->lang()` get localized variants. All other routes remain as-is.

- **Route-level vs group-level localization**: laravel-l10n attaches locales to individual routes via `->lang()`. Groups are supported via `Route::lang()->group()`, but each route inside can also have its own `->lang()` call. There are no global "localized route group" wrappers.

- **No automatic redirects**: laravel-l10n does not automatically redirect users to their preferred locale URL. If you need redirect behavior, implement it in your application (e.g., a middleware that redirects based on `app()->getPreferredLocale()`).

- **Canonical route preserved**: the original (non-localized) route remains registered with its original name. Localized variants get `.{locale}` appended to the name. `L10n::is('about')` matches all variants.

- **URL generation via `lang` parameter**: instead of helper methods like `getLocalizedURL()`, pass `['lang' => $locale]` to the standard `route()` and `action()` helpers. The `lang` key is consumed internally.

- **Preferred locale is separate from route locale**: `app()->getLocale()` reflects the matched route's locale (set by `SetLocale`). `app()->getPreferredLocale()` reflects the user's detected preference (set by `SetPreferredLocale`). These may differ. `SetPreferredLocale` does not change the current route locale and does not redirect.

## Quick decision checklist

Use the discovery scan (Step 2) to decide which optional migrations to apply.

- If you find `localizationRedirect` or `getLocalizedURL()` usage: add an application redirect middleware that reads `app()->getPreferredLocale()` and redirects to the localized URL (built via `route(..., ['lang' => $locale])`).
- If you find `LocaleCookieRedirect`: implement a custom `LocaleResolver` (reads cookie) and register it in `L10n::$preferredLocaleResolvers`. Add redirect middleware if needed.
- If you find `LocaleSessionRedirect`: keep `SessionLocale` in resolvers. Add redirect middleware if the old behavior was redirecting.
- If you find `transRoute()` or `routes.*` keys: migrate `lang/{locale}/routes.php` to laravel-l10n's key/value format.
- If you find domain-based routing: add domain translations to `lang/{locale}/routes.php` using the original domain as the key.

**Redirect middleware sketch (optional):**

```php
public function handle(Request $request, Closure $next): Response
{
    $response = $next($request);

    $route = $request->route();
    if (! $route || ! $route->getName()) {
        return $response;
    }

    $preferred = app()->getPreferredLocale();
    if (! $preferred || app()->isFallbackLocale($preferred)) {
        return $response;
    }

    $canonical = $route->canonical();
    $url = route($canonical->getName(), array_merge($route->parameters(), ['lang' => $preferred]));

    return redirect()->to($url);
}
```
