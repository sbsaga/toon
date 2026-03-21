# Migration Guide

## Upgrading to the Current Release

The package API remains the same, and the package now defaults to `legacy` compatibility mode so upgrades stay safer for existing applications.

## What Changed

- Modern mode unwraps table blocks into cleaner row lists.
- Modern mode no longer silently truncates tables when `max_preview_items` is lower than the dataset size.
- Modern mode avoids tabular output when a row contains nested arrays or objects.
- Key normalization is less destructive in modern mode.
- Global helpers and a collection macro are now available:
  - `toon_encode()`
  - `toon_decode()`
  - `toon_diff()`
  - `collect(...)->toToon()`

## Default Upgrade Behavior

You do not need to opt into legacy mode after upgrading because it is already the default:

```php
// config/toon.php
'compatibility_mode' => 'legacy',
```

Legacy mode preserves:

- lowercased keys
- preview-limited tabular rendering
- legacy inline flattening behavior

## Recommended Upgrade Path

1. Upgrade the package.
2. Run your existing application snapshots or integration tests.
3. Stay on `legacy` mode if you need previous output behavior.
4. Migrate to `modern` mode once downstream consumers are updated and you want the newer round-trip behavior.
