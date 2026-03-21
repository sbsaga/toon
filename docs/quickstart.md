# Quickstart

## Installation

```bash
composer require sbsaga/toon
```

Laravel package discovery registers the service provider and facade automatically.

## Publish Configuration

```bash
php artisan vendor:publish --provider="Sbsaga\Toon\ToonServiceProvider" --tag=config
```

Available config keys:

- `escape_style`
- `delimiter`
- `min_rows_to_tabular`
- `max_preview_items`
- `coerce_scalar_types`
- `strict_mode`
- `compatibility_mode`

Default note:

- `compatibility_mode` defaults to `legacy` for upgrade safety
- use `modern` for new projects when you want cleaner nested round trips

## Basic Encode and Decode

```php
use Sbsaga\Toon\Facades\Toon;

$payload = [
    'team' => 'platform',
    'users' => [
        ['id' => 1, 'name' => 'Alice', 'active' => true],
        ['id' => 2, 'name' => 'Bob', 'active' => false],
    ],
];

$toon = Toon::encode($payload);
$decoded = Toon::decode($toon);
```

## Helpers

```php
$toon = toon_encode($payload);
$decoded = toon_decode($toon);
$diff = toon_diff($payload);
```

## Collection Macro

```php
$toon = collect($payload['users'])->toToon();
```

## Compare JSON vs TOON

```php
$report = Toon::diff($payload);

/*
[
    'json_chars' => 1234,
    'toon_chars' => 789,
    'saved_chars' => 445,
    'savings_percent' => 36.06,
    'json_tokens_estimate' => 309,
    'toon_tokens_estimate' => 198,
    'saved_tokens_estimate' => 111,
]
*/
```

## Compatibility Tip

If you are upgrading from older package output and need previous key normalization or preview-limited tables, set:

```php
// config/toon.php
'compatibility_mode' => 'legacy',
```
