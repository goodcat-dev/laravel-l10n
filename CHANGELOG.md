# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Added

- Adds contracts for better type hinting.
- Adds `LocalizedRoute::uriWithoutPrefix()` method.

### Changed

- Renames `BrowserLocale` to `BrowserPreferredLocale`.

## v0.2.0

Released on _**2025-12-04**_.

### Added

- Removes `{lang}` parameter from routes.
- Adds `l10n.php` config file.
- Auto-registers `LocalizedUrlGenerator` as `url`.

### Changed

- Moves `$localizedViewsPath` to `RegisterLocalizedViewsPath::class`.

## v0.1.3

Released on _**2025-11-25**_.

### Added

- Makes `{lang}` parameter optional.
- Sets route locale during url generation.
- Makes `{lang}` parameter invisible to controllers.
- Adds `L10n::forgetRoute()` method.

### Fixed

- Cached `{lang}` parameter during route registration.

## v0.1.2

Released on _**2025-09-22**_.

### Added

- Supports lang files.

## v0.1.1

Released on _**2025-09-01**_.

### Added

- Tests.
- Laravel Pint, CS Fixer.

## v0.1.0

Initial release.
