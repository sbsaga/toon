# Replacer Recipes

Replacers let you transform data before TOON encoding without changing your original payload.

## Signature

```php
fn (array $path, string|int|null $key, mixed $value): mixed
```

- return `Toon::skip()` to remove a key or list item
- return any other value to keep/transform it

## Basic Usage

```php
use Sbsaga\Toon\Facades\Toon;

$toon = Toon::encodeWith($payload, function (array $path, string|int|null $key, mixed $value) {
    if ($key === 'debug') {
        return Toon::skip();
    }

    return $value;
});
```

## Recipe: Redact Secrets

```php
$toon = Toon::encodeWith($payload, function (array $path, string|int|null $key, mixed $value) {
    if (in_array($key, ['email', 'token', 'api_key'], true)) {
        return '[redacted]';
    }

    return $value;
});
```

## Recipe: Remove Internal Branches by Path

```php
$toon = Toon::encodeWith($payload, function (array $path, string|int|null $key, mixed $value) {
    if ($path === ['meta'] && $key === 'trace') {
        return Toon::skip();
    }

    return $value;
});
```

## Recipe: Normalize Scalars

```php
$toon = Toon::encodeWith($payload, function (array $path, string|int|null $key, mixed $value) {
    if ($key === 'active') {
        return $value ? 'yes' : 'no';
    }

    return $value;
});
```

## Helper Variant

```php
$toon = toon_encode_with($payload, function (array $path, string|int|null $key, mixed $value) {
    return $key === 'debug' ? \Sbsaga\Toon\Toon::skip() : $value;
});
```

## Notes

- The replacer is called recursively for nested values.
- Skipping at root level returns an empty TOON string.
- Existing `encode()`/`convert()` behavior is unchanged if you do not use replacers.
