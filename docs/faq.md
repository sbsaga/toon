# FAQ

## Is TOON a replacement for JSON?

No. JSON remains the best default for APIs and transport. TOON is most useful when readability and payload size matter inside prompts, logs, fixtures, or internal snapshots.

## Does TOON always save tokens?

Not always. It helps most when your payload has repeated field names or repeated object shapes. Flat or very small payloads may not benefit much.

## Can I decode TOON back into PHP arrays?

Yes. `Toon::decode()` and `toon_decode()` both return arrays.

## What is the safest mode for new projects?

`modern` mode. It avoids silent table truncation and keeps nested rows expanded when a table would lose information.

## What is `strict_mode` for?

It makes decoding fail fast when a TOON table is malformed. This is useful for CI fixtures, imports, and defensive parsing.

## Why does the package have both facade methods and helpers?

Some teams prefer explicit facade usage, while others want quick procedural helpers in templates, scripts, or jobs. Both are supported.
