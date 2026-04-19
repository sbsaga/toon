# FAQ

## Is TOON a replacement for JSON?

No. JSON should remain your default for external API transport and cross-system interoperability.

TOON is best for internal human-readable structured text:

- prompts
- logs
- fixtures
- internal exports

## Does TOON always save tokens?

Not always. Savings are strongest when payloads have repeated object shapes and repeated field names.

Measure with:

```php
\Sbsaga\Toon\Facades\Toon::diff($payload);
```

## Can I decode TOON back to arrays reliably?

Yes. `Toon::decode()` and `toon_decode()` return arrays.

For stricter validation:

- use `Toon::validate($toon, true)` before decode
- or decode with CLI `--strict` for fixture/import checks

## Which mode should I use?

- `legacy`: safest for existing production systems and upgrades
- `modern`: recommended for new projects or controlled migrations

## Do I need to change existing code to upgrade?

Usually no.

Core APIs remain unchanged:

- `Toon::convert()`
- `Toon::encode()`
- `Toon::decode()`
- `Toon::estimateTokens()`

New features are additive.

## How do I prevent sensitive fields from being encoded?

Use a replacer:

```php
Toon::encodeWith($payload, function (array $path, string|int|null $key, mixed $value) {
    return in_array($key, ['password', 'token'], true)
        ? Toon::skip()
        : $value;
});
```

## Is TOON safe for production logs?

Yes, if you apply redaction and follow normal logging policy.

Recommended:

- redact secrets/emails/tokens
- cap payload size by business rules
- keep strict mode for untrusted imported TOON

## When should I use CLI instead of facade/helpers?

Use CLI for:

- fixture conversion in CI
- file-to-file batch conversions
- shell pipelines and scripts

Use facade/helpers for:

- request/job runtime flows
- in-app transformations

## Can I stream TOON instead of handling one large string?

Yes:

- `Toon::encodeLines()` for line iteration
- `Toon::decodeFromLines()` for rebuilding arrays from lines

## What if output changed after upgrade?

First action:

1. ensure `compatibility_mode=legacy`
2. re-run your snapshot/contract tests
3. review new replacer/strict settings

See [Migration guide](migration.md) and [Production playbook](production-playbook.md).
