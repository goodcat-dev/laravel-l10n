# Laravel L10n

[![Latest Version on Packagist](https://img.shields.io/packagist/v/goodcat/laravel-l10n.svg?style=flat-square)](https://packagist.org/packages/goodcat/laravel-l10n)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/goodcat-dev/laravel-l10n/test.yml?branch=main&label=test&style=flat-square)](https://github.com/goodcat-dev/laravel-l10n/actions?query=workflow%3Atest+branch%3Amain)

An opinionated Laravel package for app localization.

## Quickstart

Get started with `laravel-l10n` in three steps.

1. Download the package via Composer.
   ```sh
   composer require goodcat/laravel-l10n
   ```
2. Add the locale middlewares to your `bootstrap/app.php` file.
   ```php
   return Application::configure(basePath: dirname(__DIR__))
       ->withMiddleware(function (Middleware $middleware): void {
           $middleware->web(prepend: [
               \Goodcat\L10n\Middleware\SetLocale::class,
               \Goodcat\L10n\Middleware\setPreferredLocale::class,
           ]);
       });
   ```
3. Define localized routes using the `lang()` method.
   ```php
   Route::get('/example', Controller::class)
       ->lang(['fr', 'de', 'it', 'es']);
   ```

That's it. You're all set to start using `laravel-l10n`.

## Configuration

To customize the package behavior, publish the configuration file:

```sh
php artisan vendor:publish --provider="Goodcat\L10n\L10nServiceProvider"
```

### Add Locale Prefix

By default, this package **adds the locale prefix** to translated routes, except for the fallback locale.

This means a route like `/example` will be served by the clean URL `/example` for the default language (e.g. English), while other locales will include their prefix (e.g. `/es/ejemplo`, `/it/esempio`).

If you prefer to hide the locale prefix for all languages, set `add_locale_prefix` to `false` in your `config/l10n.php` file.

```php
// config/l10n.php
return [
    'add_locale_prefix' => false,
];
```

After this change, routes will use translated URIs without locale prefixes (e.g. `/ejemplo` instead of `/es/ejemplo`).

## Route translations

Use the `lang()` method to define which locales a route should support. Route translations are managed via language files.

### Translations via Language Files

Manage route translations in dedicated language files. This approach keeps your routes clean and centralizes your translations, making them easier to manage.

The expected file structure is as follows:

```txt
/lang
├── /es
│   └── routes.php
├── /fr
│   └── routes.php
├── /it
│   └── routes.php
```

Inside your `routes.php` file, map the original route URI to a translated slug:

```php
// lang/es/routes.php
return [
    'example' => 'ejemplo',
];
```

Then define the route with the locales it should support:

```php
Route::get('/example', Controller::class)
    ->lang(['es', 'fr', 'it']);
```

This will generate:
- `/example` (fallback locale)
- `/es/ejemplo` (Spanish, translated via language file)
- `/fr/example` (French, no translation defined)
- `/it/example` (Italian, no translation defined)

> [!NOTE]
> The key should be the route URI **without** the leading slash. For example, for `Route::get('/example')`, the key should be `example`.

### Route groups

To avoid repetitive language definitions on every single route, you can use `Route::lang()->group()`:

```php
Route::lang(['es', 'it'])->group(function () {
    Route::get('/example', fn () => 'Hello, World!');
    Route::get('/another', fn () => 'Another route');
});
```

All routes inside the group will inherit the locale definitions.

## URL Generation

The package automatically replaces Laravel's default URL generator with `LocalizedUrlGenerator`, ensuring that the `route()` helper generates the correct URLs for the current locale without any extra configuration.

> [!NOTE]
> If you need to use a custom URL generator, you can override it in your `AppServiceProvider` by aliasing your own implementation to the `url` service.

### Using the `route()` and `action()` Helpers

Once the generator is registered, the `route()` helper will intelligently create URLs based on the current application locale.

- **For the current locale**: The helper automatically generates the correct URL based on the active language.
- **For a specific locale**: You can explicitly request a URL for a different language by passing the `lang` parameter to the `route()` helper.

```php
// Assuming the current locale is 'en'
route('example'); // Returns "/example"

// To generate a URL for a different locale
route('example', ['lang' => 'fr']); // Returns "/fr/example"

// If a translation exists for 'es' in lang/es/routes.php, the translated slug is used
route('example', ['lang' => 'es']); // Returns "/es/ejemplo"
```

The `action()` helper works the same way:

```php
action(Controller::class, ['lang' => 'es']); // Returns "/es/ejemplo"
```

## Localized views

This package makes it easy to organize your views by language. The application's view loader is configured to automatically search for a localized version of a view before falling back to the generic one.

### How it works

When you render a view, the system follows a specific search order based on the current application locale.

- **Locale-specific path**: The application first tries to find the view within a folder that matches the current locale. For example, if the locale is set to `it`, it will look for the `example` view in `resources/views/it/example.blade.php`.
- **Generic path**: If the view is not found in the locale-specific folder, it will then fall back to the generic `resources/views/example.blade.php`.

This makes it straightforward to organize your views with a clean, language-based folder structure, like the one below.

```
/resources/views
├── example.blade.php
├── /it
│   └── example.blade.php
└── /es
    └── example.blade.php
```

The `example.blade.php` file in the root views folder can serve as your default template, while the localized versions (`it/example.blade.php`, `es/example.blade.php`) contain language-specific content or layouts.

## User Locale Preference

This package provides a robust mechanism for automatically detecting a user's preferred language.
It adds the `app()->getPreferredLocale()` and `app()->setPreferredLocale()` methods to your Laravel application.

The `setPreferredLocale` middleware is responsible for populating the preferred locale. It does this by checking a series of configurable **preferred locale resolvers**.

By default, the package checks the following sources in order:

1. **UserPreferredLocale**: Checks if the authenticated user has a preferred locale (the user model must implement a `preferredLocale()` method).
2. **BrowserPreferredLocale**: Falls back to the browser's `Accept-Language` header.

### Customizing Resolvers

You can customize the resolvers by setting the static property on the `L10n` class:

```php
use Goodcat\L10n\L10n;
use Goodcat\L10n\Resolvers\BrowserPreferredLocale;

L10n::$preferredLocaleResolvers = [
    new BrowserPreferredLocale,
];
```

### Checking Fallback Locale

The package also adds a helper method to check if a locale is the fallback locale:

```php
app()->isFallbackLocale('en'); // true if 'en' is the fallback locale
```