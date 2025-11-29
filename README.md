# Laravel L10n

[![Latest Version on Packagist](https://img.shields.io/packagist/v/goodcat/laravel-l10n.svg?style=flat-square)](https://packagist.org/packages/goodcat/laravel-l10n)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/goodcat-dev/laravel-l10n/test.yml?branch=main&label=test&style=flat-square)](https://github.com/goodcat-dev/laravel-l10n/actions?query=workflow%3Atest+branch%3Amain)

An opinionated Laravel package for app localization.

> [!NOTE]
> This package is in early development. The API may change as features are refined. Feedback and contributions are welcome.

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
               \Goodcat\L10n\Middleware\DetectPreferredLocale::class,
           ]);
       });
   ```
3. Define localized routes using the `lang()` method.
   ```php
   Route::get('/example', Controller::class)
       ->lang([
            'fr', 'de',
            'it' => 'esempio',
            'es' => 'ejemplo'
       ]);
   ```

That's it. You're all set to start using `laravel-l10n`.

## Configuration

To customize the package behavior, publish the configuration file:

```sh
php artisan vendor:publish --provider="Goodcat\L10n\L10nServiceProvider"
```

### Hide Default Locale

By default, this package **hides the default locale** from your application's URLs. The default locale is the `fallback_locale` defined in your `config/app.php` file.

This means a route like `{lang}/example` will be served by the clean URL `/example` for the default language (e.g. English), while other locales will still include their prefix (e.g. `fr/example`).

If you prefer to include the locale prefix for all languages, set `hide_default_locale` to `false` in your `config/l10n.php` file. 
After this change, the `{lang}/example` route will be served by `en/example` for the default locale and `fr/example` for French, ensuring a consistent URL structure across all languages.

### Hide Alias Locale

When you define route aliases (e.g., `'it' => 'esempio'`), by default the locale prefix is still included in the URL (`/it/esempio`).

If you want to hide the locale prefix for aliased routes, set `hide_alias_locale` to `true`. 
This will generate `/esempio` instead of `/it/esempio`, creating cleaner URLs for your translated routes.

> [!WARNING]
> When `hide_alias_locale` is set to `true`, be careful to avoid route collisions. 
> For example, if you define `['it' => 'esempio']`, the package will generate the URL `/esempio` without the locale prefix. 
> Make sure this doesn't conflict with other routes in your application (e.g., an existing `/esempio` route for the default locale). 
> Always verify your route list with `php artisan route:list` after enabling this option.

## Route translations

Use the `lang()` method to define route translations for different locales.
This approach lets you manage multilingual URLs in an intuitive way.

### Route groups

To avoid repetitive language definitions on every single route, you can use a **route group**.
This approach is useful when you have multiple routes that share the same set of locales.

In the example below, the route group defines the supported locales (`fr`, `de`, `it`, `es`) and a `{lang}` URL prefix.
Inside the group, the `example` route inherits these settings, but you can add specific translations with the `lang()` method.

```php
Route::group([
    'lang' => ['fr', 'de', 'it', 'es'],
    'prefix' => '{lang}'
], function () {
    Route::get('/example', Controller::class)
        ->lang(['it' => 'esempio']);
});
```

> [!NOTE]
> The lang() method is not designed to be a standalone group method.
> The syntax `Route::lang()->group()` is not supported.
> Instead, you must define the lang key directly inside the `Route::group()` array, as shown in the example above.

### Translations via Language Files

In addition to defining route translations inline, you can also manage them in dedicated language files.
This approach keeps your routes clean and centralizes your translations, making them easier to manage.
The expected file structure is as follows:

```txt
/lang
├── /fr
│   └── /routes.php
├── /de
│   └── /routes.php
```

Inside your routes.php file, you can map the original route URI to a translated slug.
For example, given the route `Route::get('/example')->lang(['fr'])`, your translation file would look like this:

```php
return [
    'example' => 'exemple'
]
```

> [!NOTE]
> The key should be the route URI **without the prefix**, and the value is the translated slug.
> For example, given the route `Route::get('/example')->prefix('{lang}')`, the key should be `example`.

## URL Generation

The package automatically replaces Laravel's default URL generator with `LocalizedUrlGenerator`, ensuring that the `route()` helper generates the correct URLs for the current locale without any extra configuration.

> [!NOTE]
> If you need to use a custom URL generator, you can override it in your `AppServiceProvider` by aliasing your own implementation to the `url` service.

### Using the `route()` Helper

Once the generator is registered, the `route()` helper will intelligently create URLs based on the current application locale.

- **For the current locale**: The helper automatically generates the correct URL based on the active language.
- **For a specific locale**: You can explicitly request a URL for a different language by passing the `lang` parameter to the `route()` helper.

```php
// Assuming the current locale is 'en'
route('example'); // Returns "/example"

// To generate a URL for a different locale
route('example', ['lang' => 'fr']); // Returns "/fr/example"

// If a translation exists for 'it', the translated slug is used
route('example', ['lang' => 'it']); // Returns "/it/esempio"
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
It adds the `\app()->getPreferredLocale()` and `\app()->setPreferredLocale()` methods to your Laravel application.

The `DetectPreferredLocale` middleware is responsible for populating the preferred locale. It does this by checking a series of configurable **preferred locale resolvers**.

By default, the package checks the following sources in order:

1. **User-defined preference**: It first looks for a preferredLocale() method on the authenticated user model. To use this, your user model must implement the HasLocalePreference interface.
   This allows logged-in users to have a persistent language setting.
2. **Browser language**: If no user-defined preference is found, it inspects the `Accept-Language` header of the user's browser (e.g. `en-US,en;q=0.9`).

### Custom Locale Resolvers

If you need a different way to determine the preferred locale, you can create a custom resolver.
This is useful for more specific logic, such as checking a session value, a cookie, or a query parameter.

To create a custom resolver, simply implement the `PreferredLocaleResolver` interface.
The `resolve()` method receives the current request and should return the locale string or `null` if it can't be determined.

```php
use Goodcat\L10n\Resolvers\PreferredLocaleResolver;
use Illuminate\Http\Request;

class CookieLocaleResolver implements PreferredLocaleResolver
{
    public function resolve(Request $request): ?string
    {
        return $request->cookie('locale');
    }
}
```

Then, you must register your custom resolver in the `boot()` method of your `AppServiceProvider`.
By placing it at the beginning of the array, your resolver will be checked before the default ones.

```php
use Goodcat\L10n\L10n;
use Illuminate\Support\ServiceProvider

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        L10n::$preferredLocaleResolvers = [
            new CookieLocaleResolver,
            ...L10n::getPreferredLocaleResolvers(),
        ];
    }
}
```

This flexible approach ensures you have full control over how your application detects and manages a user's language preferences.
