# Laravel L10n

[![Latest Version on Packagist](https://img.shields.io/packagist/v/goodcat/laravel-l10n.svg?style=flat-square)](https://packagist.org/packages/goodcat/laravel-l10n)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/goodcat-dev/laravel-l10n/test.yml?branch=main&label=test&style=flat-square)](https://github.com/goodcat-dev/laravel-l10n/actions?query=workflow%3Atest+branch%3Amain)

An opinionated Laravel package for app localization.

## Table of Contents

- [Quickstart](#quickstart)
- [Localized Routes](#localized-routes)
- [URL Generation](#url-generation)
- [Route Caching](#route-caching)
- [Locale Preference](#locale-preference)
- [Helpers](#helpers)
- [Components](#components)
- [Localized Views](#localized-views)
- [JavaScript URL Generation](#javascript-url-generation)

## Quickstart

Get started with `laravel-l10n` in two steps.

1. Download the package via Composer.
   ```sh
   composer require goodcat/laravel-l10n
   ```
2. Define localized routes using the `lang()` method.
   ```php
   Route::get('/example', Controller::class)
       ->lang(['fr', 'de', 'it', 'es']);
   ```

That's it. You're all set to start using `laravel-l10n`.

## Localized Routes

### Defining Localized Routes

Use the `lang()` method to define which locales a route should support:

```php
Route::get('/example', Controller::class)
    ->lang(['es', 'fr', 'it']);
```

This will generate:
- `/example` (fallback locale)
- `/es/ejemplo` (Spanish, translated via language file)
- `/fr/example` (French, no translation defined)
- `/it/example` (Italian, no translation defined)

The fallback locale here is Laravel's own `fallback_locale` (`APP_FALLBACK_LOCALE` in `.env`), it must match the language your routes and content are actually written in.

Localized routes automatically set the application's locale to their language. The canonical route uses `APP_FALLBACK_LOCALE`. Routes without `lang()` leave the application's locale unchanged, which defaults to `APP_LOCALE`.

Listing the fallback locale in `lang()` is harmless: the canonical route already serves it, so no extra route is registered.

#### Route groups

To avoid repetitive language definitions on every single route, you can use `Route::lang()->group()`:

```php
Route::lang(['es', 'it'])->group(function () {
    Route::get('/example', fn () => 'Hello, World!');
    Route::get('/another', fn () => 'Another route');
});
```

All routes inside the group inherit its locales. Use `lang()` on an individual route to add more languages:

```php
Route::lang(['es', 'it'])->group(function () {
    Route::get('/example', fn () => 'Hello, World!'); // es, it
    Route::get('/another', fn () => 'Another route')
        ->lang(['fr']); // es, it, fr
});
```

### Route Strategy

The `route_strategy` option in `config/l10n.php` controls how locale prefixes are applied:

- `prefix_except_default` (default) keeps the fallback locale unprefixed (e.g. `/example`, `/es/ejemplo`).
- `prefix` prefixes every locale and does not register an unprefixed route (e.g. `/en/example`, `/es/ejemplo`).
- `no_prefix` uses translated URIs without locale prefixes (e.g. `/example`, `/ejemplo`).

> [!NOTE]
> `config/l10n.php` is created by publishing the package config: `php artisan vendor:publish --tag=l10n-config`.

### Translating Route URIs

Manage route translations in dedicated language files. The expected file structure is as follows:

```txt
/lang
├── /es
│   └── routes.php
├── /fr
│   └── routes.php
└── /it
    └── routes.php
```

Inside your `routes.php` file, map the original route URI to a translated slug:

```php
// lang/es/routes.php
return [
    'example' => 'ejemplo',
];
```

If no translation is provided for a given locale, the original URI is used as-is.

> [!WARNING]
> With the `no_prefix` strategy, a translation that shares its domain and URI with the canonical route or an earlier translation is skipped and falls back to the canonical route.

> [!NOTE]
> The key should be the route URI **without** the leading slash. For example, for `Route::get('/example')`, the key should be `example`.

For routes with a custom binding key, omit `:slug` from the translation key:

```php
// routes/web.php
Route::get('/article/{post:slug}', [PostController::class, 'show'])
    ->lang(['it']);
```

```php
// lang/it/routes.php
return [
    'article/{post}' => 'articolo/{post}',
];
```

The localized route still binds `post` by its slug.

### Domain Translations

If your application uses domain-based routing, you can translate domains in the same `routes.php` language files. The key is the original domain string:

```php
// lang/es/routes.php
return [
    'example' => 'ejemplo',
    'example.com' => 'es.example.com',
];
```

## URL Generation

### Using the `route()` and `action()` Helpers

Use Laravel's `route()` helper as usual to generate URLs in the current language. Pass `lang` to link to another language:

```php
// Assuming the current locale is 'fr'
route('example'); // Returns "/fr/example"

// To generate a URL for a different locale
route('example', ['lang' => 'en']); // Returns "/example"

// If a translation exists for 'es' in lang/es/routes.php, the translated slug is used
route('example', ['lang' => 'es']); // Returns "/es/ejemplo"
```

The `action()` helper works the same way:

```php
action(Controller::class, ['lang' => 'es']); // Returns "/es/ejemplo"
```

> [!WARNING]
> `lang` is reserved for selecting the URL's language. Use a different name for route parameters: URLs containing `{lang}` cannot be generated with `route()` or `action()`.

## Route Caching

Localized routes support Laravel's standard route caching:

```sh
php artisan route:cache
```

## Locale Preference

To detect the user's preferred language, add `SetPreferredLocale` to your existing `bootstrap/app.php` configuration:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \Goodcat\L10n\Middleware\SetPreferredLocale::class,
    ]);
})
```

This optional middleware resolves a preference without changing the active locale.

By default, the package checks the following sources in order:

1. ~~**SessionLocale**: Checks if a locale was set in the session.~~ @deprecated
2. **UserLocale**: Checks if the authenticated user has a preferred locale (the user model must implement Laravel's `Illuminate\Contracts\Translation\HasLocalePreference` interface).
3. **BrowserLocale**: Falls back to the browser's `Accept-Language` header, keeping all its languages in order of preference.

### Redirecting to the Preferred Locale

Optionally add `RedirectToPreferredLocale` after `SetPreferredLocale` in the web middleware group:

```php
$middleware->web(append: [
    \Goodcat\L10n\Middleware\SetPreferredLocale::class,
    \Goodcat\L10n\Middleware\RedirectToPreferredLocale::class,
]);
```

On the first GET request to a route with translations, the user is redirected to their preferred language if it is available and differs from the current one (HTTP 302). The check runs once per session, even when no redirect is needed, so the user can switch languages freely afterward.

This feature requires a session, which Laravel's `web` group provides. When using translated domains, configure a shared session across those domains.

### Customizing Resolvers

To choose which sources to check and in what order, set the resolvers in the `boot()` method of your `AppServiceProvider`:

```php
use Goodcat\L10n\L10n;
use Goodcat\L10n\Resolvers\BrowserLocale;

public function boot(): void
{
    L10n::$preferredLocaleResolvers = [
        new BrowserLocale,
    ];
}
```

### Creating a Custom Resolver

Implement the `LocaleResolver` interface to create your own resolver:

```php
namespace App\Resolvers;

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

Then add it to the resolvers in your `AppServiceProvider::boot()` method. Your application is responsible for setting the cookie:

```php
use App\Resolvers\CookieLocale;
use Goodcat\L10n\L10n;
use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\UserLocale;

public function boot(): void
{
    L10n::$preferredLocaleResolvers = [
        new CookieLocale,
        new UserLocale,
        new BrowserLocale,
    ];
}
```

## Helpers

This package adds several helper methods to your Laravel application.

### Application Helpers

```php
// Get the user's preferred locales, in order of preference
app()->getPreferredLocales(); // Returns ?array, e.g. ['es_ES', 'es', 'en']

// Get the user's first preferred locale
app()->getPreferredLocale(); // Returns ?string, e.g. 'es_ES'

// Get the best match among the given locales, or null
app()->getPreferredLocale(['en', 'es']); // Returns ?string, e.g. 'es'

// Set the user's preferred locales (dispatches PreferredLocaleUpdated event)
app()->setPreferredLocale('es');
app()->setPreferredLocale(['es_ES', 'es', 'en']);

// Check if a locale is the fallback locale
app()->isFallbackLocale('en'); // Returns bool
```

When given a list of locales, `getPreferredLocale()` follows the preferences in order and, for each one, looks for an exact match, then its language (`es_ES` matches `es`), then the first variant of the same language in the given list (`es_ES` matches `es_MX`). It returns `null` when nothing matches, so you can provide a default explicitly:

```php
app()->getPreferredLocale(['en', 'es']) ?? 'en';
```

### Route Helpers

```php
// Get the route's language, or the fallback locale for a non-localized route
$request->route()->locale(); // Returns string
```

### Route Matching

Use `L10n::is()` to check if the current route matches a given pattern, regardless of the locale:

```php
L10n::is('dashboard');  // Matches /dashboard, /es/dashboard, /it/bacheca, etc.
L10n::is('admin.*');    // Wildcard patterns are supported, just like Route::is()
```

Use the original route name in your patterns, without a locale suffix.

## Components

The package provides Blade components for common localization needs.

### Alternate Hreflang Links

The package provides a Blade component that generates `<link rel="alternate" hreflang="...">` tags following [Google's guidelines for localized versions](https://developers.google.com/search/docs/specialty/international/localized-versions).

Add the component to the `<head>` of your layout:

```html
<head>
    <x-l10n::alternate />
</head>
```

For a route with `es` and `it` translations, this will render:

```html
<link rel="alternate" hreflang="en" href="https://example.com/products/42" />
<link rel="alternate" hreflang="es" href="https://example.com/es/productos/42" />
<link rel="alternate" hreflang="it" href="https://example.com/it/products/42" />
<link rel="alternate" hreflang="x-default" href="https://example.com/products/42" />
```

The component includes all localized variants, the fallback locale, and an `x-default` entry pointing to the canonical route. It renders nothing for routes without translations.

### Locale Switcher

The package provides a Blade component that renders a `<select>` element for switching between available locales. When the user selects a different locale, the page reloads to the corresponding localized URL.

```html
<x-l10n::switcher />
```

For a route with `es` and `it` translations, this will render:

```html
<select onchange="window.location = this.value">
    <option value="https://example.com/products/42" selected>en</option>
    <option value="https://example.com/es/productos/42">es</option>
    <option value="https://example.com/it/products/42">it</option>
</select>
```

The current locale is automatically selected. The component renders nothing for routes without translations.

You can pass any HTML attribute to the component:

```html
<x-l10n::switcher class="locale-select" id="locale" />
```

### Customizing the Templates

To customize the HTML output of the Blade components, publish the views:

```sh
php artisan vendor:publish --tag=l10n-views
```

Edit the published templates in `resources/views/vendor/l10n/components/`. Each template lists the variables available for customization.

## Localized Views

Organize language-specific views in folders named after their locale:

```
/resources/views
├── example.blade.php
├── /it
│   └── example.blade.php
└── /es
    └── example.blade.php
```

Call `view('example')` as usual. When the current locale is `it`, it renders `resources/views/it/example.blade.php` if available, otherwise `resources/views/example.blade.php`.

## JavaScript URL Generation

This package provides helper functions for generating localized URLs in your JavaScript/TypeScript frontend.
Choose the helper for [Wayfinder](https://github.com/laravel/wayfinder) or [Ziggy](https://github.com/tightenco/ziggy).
Given the following route definition:

```php
Route::get('/foo/{id}', Controller::class)
    ->lang(['it', 'es'])
    ->name('foo');
```

### Wayfinder

If you're using Wayfinder, publish the TypeScript helper:

```sh
php artisan vendor:publish --tag=l10n-wayfinder
```

> [!NOTE]
> The `route()` helper only works with named routes, actions are not supported.

This creates a `resources/js/l10n.ts` file exporting a `route(routes, params?, options?)` helper that selects the appropriate localized route based on the current locale.

Import the `route` helper and pass Wayfinder's generated route functions:

```ts
import { route } from '@/l10n';
import foo from '@/routes/foo';

const esUrl = route(foo, { id: 1, lang: 'es' }).url;
```

The `LocalizedRoutes` type is also exported, to type your own wrappers and composables.

The locale is resolved in the following order:
1. The `lang` parameter, if provided.
2. The `lang` attribute of the `<html lang="en">` element, normalized to Laravel's locale format (`pt-BR` matches a `pt_BR` route).
3. The canonical route, when no localized route matches.

During server-side rendering, pass `lang` explicitly to select a localized route; without it, the helper falls back to the canonical route.

To call the canonical route directly, use `foo.__canonical({ id: 1 })`.

### Ziggy

If you're using Ziggy, publish the JavaScript helper:

```sh
php artisan vendor:publish --tag=l10n-ziggy
```

This creates a `resources/js/l10n.js` file with a `route` function.

Use `route()` as a drop-in replacement for Ziggy's `route()` function:

```js
import { route } from '@/l10n';

route('foo', { id: 1, lang: 'it' });
```

The helper uses the `lang` parameter first, then the HTML `lang` attribute, and falls back to the canonical route if no translation matches. Regional locales such as `pt-BR` also match Laravel's underscore format, `pt_BR`.

During server-side rendering, pass `lang` explicitly to select a localized route; without it, the helper falls back to the canonical route.

All Ziggy arguments are forwarded, including an explicit configuration object as the fourth argument. Calling `route()` without a route name returns Ziggy's `Router` instance as usual.
