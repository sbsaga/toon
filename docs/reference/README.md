# TOON Reference

## Public API

Facade and service methods:

- `Toon::convert(mixed $input): string`
- `Toon::encode(mixed $input): string`
- `Toon::decode(string $toon): array`
- `Toon::estimateTokens(string $toon): array`
- `Toon::diff(mixed $input): array`
- `Toon::promptBlock(mixed $input, string $fenceLabel = 'toon'): string`
- `Toon::validate(string $toon, bool $strict = true): array`
- `Toon::contentType(): string`
- `Toon::fileExtension(): string`

Global helpers:

- `toon_encode(mixed $data): string`
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
