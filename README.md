# Laravel L10n

[![Latest Version on Packagist](https://img.shields.io/packagist/v/goodcat/laravel-l10n.svg?style=flat-square)](https://packagist.org/packages/goodcat/laravel-l10n)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/goodcat-dev/laravel-l10n/tests.yaml?branch=main&label=tests&style=flat-square)](https://github.com/goodcat-dev/laravel-l10n/actions?query=workflow%3Atests+branch%3Amain)

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
               \Goodcat\L10n\Middleware\DetectPreferredLocale::class,
               \Goodcat\L10n\Middleware\SetLocale::class,
           ]);
       });
   ```
3. Register the route's supported locales using the `lang()` method.
   ```php
   Route::get('{lang}/example', Controller::class)
       ->lang(['en', 'es', 'fr', 'it']);
   ```

That's it. You're all set to start using `laravel-l10n`.

## Route translations

You can define a route translation via the `lang()` method, using the `[$locale => $translation]` syntax.

```php
Route::get('/example', Controller::class)
    ->lang([
        'it' => 'esempio',
        'es' => 'ejemplo',
    ])
```

Adding a locale without a translation requires the `{lang}` parameter to be present on the route or within a prefix.
The location of the `{lang}` attribute will change the resulting URL: on the prefix `it/esempio`, on the route `/esempio`.

```php
Route::get('/example', Controller::class)
    ->prefix('{lang}')
    ->lang(['es', 'it' => 'esempio']);
```

### Hide the default locale

Using the `{lang}` parameter results in the URL `en/example`, however the package hides the default locale by default resulting in the URL `example`.
If you want to change this behaviour set the `L10n::$hideDefaultLocale` to `false` in your `AppServiceProvider`.

```php
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        L10n::$hideDefaultLocale = false;
    }
}
```

## Localized URL

...

## Localized View

...

## Preferred locale

...
