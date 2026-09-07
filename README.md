# Laravel Violation Logger

[![Tests](https://github.com/karim-tao/laravel-violation-logger/actions/workflows/tests.yml/badge.svg)](https://github.com/karim-tao/laravel-violation-logger/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/karim-tao/laravel-violation-logger)](https://packagist.org/packages/karim-tao/laravel-violation-logger)
[![License](https://img.shields.io/packagist/l/karim-tao/laravel-violation-logger)](https://packagist.org/packages/karim-tao/laravel-violation-logger)

Logs Eloquent strict mode violations to a JSON file instead of throwing, with the line that caused them and how many times:

```json
{
    "app/Http/Controllers/PostController.php:24": {
        "App\\Models\\Post": {
            "lazy": { "author": 30 },
            "missing": { "body": 1 }
        }
    }
}
```

Run your test suite, fix the list.

## Installation

> Requires PHP 8.3+ and Laravel 12 or 13.

You can install the package via composer:

```bash
composer require karim-tao/laravel-violation-logger --dev
```

That's it — the `ViolationLoggerServiceProvider` is auto-discovered, so you're ready to go.

## Usage

Turn strict mode on:

```php
// app/Providers/AppServiceProvider.php

Model::shouldBeStrict(! $this->app->isProduction());
```

Violations now go to `storage/logs/violations.json` instead of throwing.

| Type | Switch | What it means |
| --- | --- | --- |
| `lazy` | `Model::preventLazyLoading()` | a relation was loaded outside of eager loading (N+1) |
| `missing` | `Model::preventAccessingMissingAttributes()` | an attribute was read that the query did not select |
| `discarded` | `Model::preventSilentlyDiscardingAttributes()` | a mass assigned attribute was dropped because it is not fillable |

## How it works

- The provider registers Laravel's own `handleLazyLoadingViolationUsing`, `handleMissingAttributeViolationUsing` and `handleDiscardedAttributeViolationUsing` callbacks, so nothing runs unless strict mode is on.
- The call site is the first frame of the backtrace outside `vendor/` and outside any other Composer dependency, so it always points at your code.
- Every violation is written straight away, under an exclusive lock, so requests, queued jobs and test workers can share the file.
- Counts add up until you delete the file.

## Testing

```bash
composer test
```

## License

Laravel Violation Logger is open-sourced software licensed under the [MIT license](LICENSE.md).
