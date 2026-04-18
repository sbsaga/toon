# TOON Reference

## Public API

Facade and service methods:

- `Toon::convert(mixed $input): string`
- `Toon::convertWith(mixed $input, ?callable $replacer = null): string`
- `Toon::encode(mixed $input): string`
- `Toon::encodeWith(mixed $input, ?callable $replacer = null): string`
- `Toon::decode(string $toon): array`
- `Toon::encodeLines(mixed $input): \Generator`
- `Toon::decodeFromLines(iterable $lines): array`
- `Toon::skip(): object`
- `Toon::estimateTokens(string $toon): array`
- `Toon::diff(mixed $input): array`
- `Toon::promptBlock(mixed $input, string $fenceLabel = 'toon'): string`
- `Toon::validate(string $toon, bool $strict = true): array`
- `Toon::contentType(): string`
- `Toon::fileExtension(): string`

Replacer callback contract:

- signature: `fn(array $path, string|int|null $key, mixed $value): mixed`
- return `Toon::skip()` to remove fields/items
- called recursively on nested values

Global helpers:

- `toon_encode(mixed $data): string`
- `toon_encode_with(mixed $data, callable $replacer): string`
- `toon_encode_lines(mixed $data): array`
- `toon_decode(string $toon): array`
- `toon_diff(mixed $data): array`
- `toon_prompt(mixed $data, string $label = 'toon'): string`
- `toon_validate(string $toon, bool $strict = true): array`

Collection macro:

- `collect($rows)->toToon(): string`

Opt-in trait:

- `Sbsaga\Toon\Concerns\Toonable`

## Config Keys

- `enabled`
- `escape_style`
- `delimiter`
- `min_rows_to_tabular`
- `max_preview_items`
- `coerce_scalar_types`
- `strict_mode`
- `compatibility_mode`

## Recommended Modes

- use `legacy` when protecting existing serialized output contracts
- use `modern` for new projects that want cleaner nested-data round trips

## Related Guides

- [Upgrade safety](../upgrade-safety-v1-3.md)
- [Production playbook](../production-playbook.md)
- [Cookbook](../cookbook.md)
- [Troubleshooting](../troubleshooting.md)
- [CLI conversion](../cli-conversion-guide.md)
- [Replacer recipes](../replacer-recipes.md)

## Suggested GitHub Topics

Apply these manually in the repository metadata for discoverability:

- `laravel`
- `laravel-package`
- `laravel-ai`
- `toon`
- `laravel-toon`
- `llm`
- `prompt-compression`
- `token-optimization`
- `structured-data`
- `openai`
