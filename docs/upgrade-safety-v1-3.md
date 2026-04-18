# Upgrade Safety for v1.3.0

## Why This Release Is Safe by Default

`v1.3.0` adds new APIs but does not change runtime defaults.

- default `compatibility_mode` remains `legacy`
- existing encode/decode API contracts remain unchanged
- no methods were removed
- no existing method signatures were changed

## Existing Behavior That Stays the Same

These continue to behave as before unless you opt into new options:

- `Toon::convert()`
- `Toon::encode()`
- `Toon::decode()`
- `Toon::estimateTokens()`
- `php artisan toon:convert --encode|--decode`

## New Additive Features

- Replacer APIs:
  - `Toon::convertWith()`
  - `Toon::encodeWith()`
  - `Toon::skip()`
  - `toon_encode_with()`
- Streaming convenience:
  - `Toon::encodeLines()`
  - `Toon::decodeFromLines()`
  - `toon_encode_lines()`
- CLI enhancements:
  - `--from`, `--to`, `--stats`, `--delimiter`, `--mode`, `--strict`

## Recommended Upgrade Rollout

1. Upgrade package version.
2. Keep `compatibility_mode=legacy`.
3. Run your existing snapshot/integration tests.
4. Optionally trial new APIs in one bounded flow first (for example, logs or prompt payload preparation).
5. Roll out broadly after downstream consumers are confirmed.

## Quick Post-Upgrade Checks

```bash
composer validate --strict
vendor/bin/phpunit --configuration phpunit.xml.dist
composer audit
```

For Laravel app smoke verification:

```php
use Sbsaga\Toon\Facades\Toon;

$toon = Toon::encode($payload);
$decoded = Toon::decode($toon);
$filtered = Toon::encodeWith($payload, fn (array $path, string|int|null $key, mixed $value) => $value);
```

If `encode()/decode()` outputs and downstream parsing remain stable in your app, you can ship with low risk.

## Related Guides

- [Production playbook](production-playbook.md)
- [Cookbook](cookbook.md)
- [Troubleshooting](troubleshooting.md)
