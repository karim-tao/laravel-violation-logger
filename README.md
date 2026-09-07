# Laravel Violation Logger

[![Tests](https://github.com/karim-tao/laravel-violation-logger/actions/workflows/tests.yml/badge.svg)](https://github.com/karim-tao/laravel-violation-logger/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/karim-tao/laravel-violation-logger)](https://packagist.org/packages/karim-tao/laravel-violation-logger)
[![License](https://img.shields.io/packagist/l/karim-tao/laravel-violation-logger)](https://packagist.org/packages/karim-tao/laravel-violation-logger)

Logs Eloquent strict mode violations to a JSON file instead of throwing: lazy loading, missing attributes and discarded attributes, grouped by the line of your code that caused them.

```json
{
    "app/Http/Controllers/PostController.php:24": {
        "App\\Models\\Post": {
            "lazy": ["author", "comments"]
        }
    },
    "app/Exports/PostsExport.php:41": {
        "App\\Models\\Post": {
            "missing": ["body"],
            "discarded": ["rating"]
        }
    }
}
```

Turn strict mode on, run your app or your test suite, and fix the list.

## Installation

> Requires PHP 8.3+ and Laravel 12 or 13.

You can install the package via composer:

```bash
composer require karim-tao/laravel-violation-logger --dev
```

That's it — the `ViolationLoggerServiceProvider` is auto-discovered, so you're ready to go.

## Usage

Enable Eloquent strict mode where you want it, typically outside production:

```php
// app/Providers/AppServiceProvider.php

use Illuminate\Database\Eloquent\Model;

public function boot(): void
{
    Model::shouldBeStrict(! $this->app->isProduction());
}
```

From now on a violation no longer throws: it is appended to `storage/logs/violations.json` and the code carries on. Every entry is keyed by the file and line of your application that triggered it, then by model and by type:

| Type | Laravel setting | What it means |
| --- | --- | --- |
| `lazy` | `preventLazyLoading` | a relation was loaded outside of eager loading (N+1) |
| `missing` | `preventAccessingMissingAttributes` | an attribute was read that the query did not select |
| `discarded` | `preventSilentlyDiscardingAttributes` | a mass assigned attribute was dropped because it is not fillable |

The same violation logged twice is stored once. The file grows across requests and commands until you delete it, so a full test run leaves you a complete list of what to fix.

Frames from `vendor/` and from any other Composer dependency are skipped when looking for the call site, so the line always points at your own code.

### Keeping the file empty

Delete the file, run your suite, and fail if it is back:

```bash
rm -f storage/logs/violations.json
php artisan test
test ! -f storage/logs/violations.json || (cat storage/logs/violations.json && exit 1)
```

## Testing

```bash
composer test
```

## License

Laravel Violation Logger is open-sourced software licensed under the [MIT license](LICENSE.md).
