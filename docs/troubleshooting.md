# Troubleshooting

Use this page when TOON output or decode behavior is not what you expected.

## 1) "My output changed after upgrade"

Check mode first:

```php
// config/toon.php
'compatibility_mode' => 'legacy',
```

Then verify:

- no new replacer logic was added on that path
- no delimiter override was introduced (`pipe` or `tab`)
- no strict decode option was enabled unexpectedly

## 2) "Decode fails on table rows"

Likely causes:

- declared row count does not match actual rows
- one or more rows have wrong number of columns

Fix:

- validate generated TOON text shape
- use strict mode intentionally and only where required
- run `Toon::validate($toon, true)` before decode in import flows

## 3) "Token savings are lower than expected"

TOON benefits most from repeated field structures. Small or irregular payloads can have minor savings.

Measure:

```php
$report = \Sbsaga\Toon\Facades\Toon::diff($payload);
```

## 4) "Sensitive fields appear in output"

Add a replacer and centralize policy:

```php
Toon::encodeWith($payload, function (array $path, string|int|null $key, mixed $value) {
    return in_array($key, ['password', 'token', 'secret'], true)
        ? Toon::skip()
        : $value;
});
```

## 5) "CLI command direction feels wrong"

Direction precedence:

1. `--decode` / `--encode`
2. `--from` / `--to`
3. extension autodetect (`.json` => encode, `.toon` => decode)
4. fallback decode

Use explicit options when uncertain:

```bash
php artisan toon:convert storage/app/data.input --from=json --to=toon
```

## 6) "Need confidence before enabling modern mode"

Safe approach:

1. keep production on `legacy`
2. run staging tests with `modern`
3. compare encoded output for key payloads
4. update downstream consumers if needed
5. switch production only after sign-off

## 7) "I need line-based processing"

Use:

- `Toon::encodeLines()` for iteration-friendly output
- `Toon::decodeFromLines()` to rebuild data

## Still Blocked?

When reporting an issue, include:

- package version
- Laravel and PHP versions
- `compatibility_mode`, `delimiter`, and `strict_mode`
- minimal payload sample that reproduces the issue
