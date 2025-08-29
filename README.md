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
               \Goodcat\L10n\Middleware\SetLocale::class,
               \Goodcat\L10n\Middleware\DetectPreferredLocale::class,
           ]);
       });
   ```
3. Define localized routes using the `lang()` method.
   ```php
   Route::get('{lang}/example', Controller::class)
       ->lang([
            'fr', 'de',
            'it' => 'esempio',
            'es' => 'ejemplo'
       ]);
   ```

That's it. You're all set to start using `laravel-l10n`.

## Route translations

Use the `lang()` method on your routes to define translations for different locales. 
If you define a locale without a translation - like `fr` and `de` in the example - **you must add** the `{lang}` parameter on the route.

```php
Route::get('{lang}/example', Controller::class)
    ->lang([
        'fr', 'de',
        'it' => 'esempio',
        'es' => 'ejemplo'
    ]);
```

Using the `lang()` method on each route can quickly become tedious. You can define a route group with default locales, then set the translation on the route.

```php
Route::group([
    'lang' => ['fr', 'de', 'it', 'es'],
    'prefix' => '{lang}'
], function () {
    Route::get('/example', Controller::class)
        ->lang(['it' => 'esempio']);
});
```

### Hide default locale

The default locale of your application is the `fallbak_locale` defined in the `config\app.php` file. 

By default, the packages hides the default locale from the URL, meaning the route `{lang}/example` will be served by the `/example` URL.
If you want to change this behaviour you can set the `L10n::$hideDefaultLocale` to `false` in your `AppServiceProvider`.
Now, the route `{lang}/example` will be served by `en/example` URL.

```php
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        L10n::$hideDefaultLocale = false;
    }
}
```

## URL Generation

In order to generate the localized URL for a route, you have to change the `UrlGenerator::class` instance in the container with the `LocalizedUrlGenerator::class`.
This way the `route()` helper will generate the URL for the current locale without any further customization.

```php
use Goodcat\L10n\Routing\LocalizedUrlGenerator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->alias(LocalizedUrlGenerator::class, 'url');
    }
}
```

If you want to generate the URL for a specific locale, pass the `lang` parameter to the `route()` helper.

```php
route('example') // Returns "/example" with current locale "en"
route('example', ['lang' => 'fr']); //  Returns "/fr/example"
route('example', ['lang' => 'it']); // Returns "/it/esempio"
```

## Localized views

By default, the application try first to find the view in the locale path. If not found, try to resolve in the generic folder.
In the example, the application will search the view `example` in the folder `resources/views/it`. If not found, then it will search in the generic `resources/views/`.

```php
class Controller
{
    public function __invoke(): View {
        app()->setLocale('it');

        return view('example');
    }
}
```

This makes trivial organize your views in a language-based folder structure.

```
/resources/views
  example.blade.php
  /it
    example.blade.php
  /es
    example.blade.php
```

## User locale preference

The package adds the `getPreferredLocale()` and `setPreferredLocale()` methods to the application.
The `DetectPreferredLocale` middleware is responsible for populating the preferred locale.
The preferred locale is detected first on the user, checking the `HasLocalePreference::preferredLocale()`, then checking request's accepted languages a.k.a. the browser language.

If you wish, you can create custom locale resolver by implementing the `PreferredLocaleResolver` interface, then register it on the `L10n::$preferredLocaleResolvers` property.

```php
use Goodcat\L10n\Resolvers\PreferredLocaleResolver;

class DumbResolver implements PreferredLocaleResolver
{
    public function resolve(Request $request): ?string
    {
        return 'it'; // Everybody loves Italy. :P
    }
}
```

Then you should register your custom resolver in the `AppServiceProvider`.

```php
use Goodcat\L10n; 

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        L10n::$preferredLocaleResolvers = [
            new DumbResolver,
            ...L10n::getPreferredLocaleResolvers(),
        ];
    }
}
```