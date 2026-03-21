# TOON Project Analysis

## 1. Executive Summary

`sbsaga/toon` is a Laravel package for converting structured PHP arrays or JSON into a compact text format called **TOON** (Token-Optimized Object Notation), and decoding that format back into PHP arrays.

The package is mainly related to:

- AI and LLM prompt optimization
- reducing prompt size and estimated token usage
- making structured data more readable than raw JSON
- Laravel developer tooling through facades, service providers, config publishing, and an Artisan command

In practical terms, this package helps a Laravel developer take verbose JSON-like data and compress it into a shorter human-readable notation before sending it to systems like ChatGPT, OpenAI APIs, Claude, Gemini, or Mistral.

## 2. What This Package Is For

The package solves a simple problem:

- JSON is easy for machines, but often verbose for prompts
- prompt size affects LLM cost and context limits
- developers still want output that is readable and reversible

TOON is the package's answer to that problem.

Example idea:

```json
[
  { "id": 1, "name": "Alice", "active": true },
  { "id": 2, "name": "Bob", "active": false }
]
```

can become:

```text
items[2]{id,name,active}:
  1,Alice,true
  2,Bob,false
```

This is shorter, still readable, and easier to fit into AI prompts.

## 3. Package Identity

- Package name: `sbsaga/toon`
- Type: Composer library
- Framework target: Laravel 9.x to 12.x
- PHP requirement: `>=8.1`
- Namespace: `Sbsaga\Toon`
- License: MIT
- Maintainer listed in `composer.json`: `Sagar S. Bhedodkar`
- Homepage: `https://github.com/sbsaga/toon`

## 4. High-Level Architecture

The project is small and cleanly separated:

- `src/Toon.php`
  Main service API used by the package.
- `src/Converters/ToonConverter.php`
  Encodes arrays, JSON, objects, and scalars into TOON.
- `src/Converters/ToonDecoder.php`
  Parses TOON back into PHP arrays.
- `src/ToonServiceProvider.php`
  Registers package services in Laravel.
- `src/Facades/Toon.php`
  Gives Laravel facade access like `Toon::convert(...)`.
- `src/Console/ToonConvertCommand.php`
  Adds the `php artisan toon:convert` command.
- `config/toon.php`
  Default package settings.
- `tests/`
  PHPUnit coverage for conversion, decoding, integration, and edge cases.
- `.github/workflows/tests.yml`
  CI workflow for tests and quality checks.

## 5. Main Runtime Flow

### Encoding flow

1. User passes JSON, array, object, or scalar to `Toon::convert()` or `Toon::encode()`.
2. `src/Toon.php` forwards the work to `ToonConverter`.
3. `ToonConverter` decides whether the data is:
   - associative array
   - sequential array
   - uniform array of objects
   - scalar
4. It renders the output as:
   - `key: value` lines
   - nested indented blocks
   - tabular `items[n]{field1,field2}:` blocks
5. Strings are escaped to keep commas and colons safe.

### Decoding flow

1. User passes a TOON string to `Toon::decode()`.
2. `src/Toon.php` forwards the work to `ToonDecoder`.
3. `ToonDecoder` reads the input line by line.
4. Indentation is used to rebuild nesting.
5. Table syntax is detected and converted back to arrays of rows.
6. Scalar coercion optionally converts strings like `true`, `10`, `1.5`, `null` to native PHP values.

## 6. File-by-File Functional Analysis

### `config/toon.php`

This file defines package settings.

- `enabled`
  Intended as a global on/off flag.
- `escape_style`
  Controls how text is escaped during serialization.
- `min_rows_to_tabular`
  Minimum list size before a uniform list becomes a compact table.
- `max_preview_items`
  Maximum number of rows rendered in tabular output.
- `coerce_scalar_types`
  Controls whether decoder turns strings into native PHP booleans, ints, floats, and nulls.

Important note: `enabled` is documented, but the current code never checks it. So it is effectively unused right now.

### `src/ToonServiceProvider.php`

This is the Laravel integration layer.

#### `boot(): void`

Responsibilities:

- publishes `config/toon.php` into the host Laravel app
- registers the console command when the app runs in CLI mode

Developer value:

- makes the package feel like a normal Laravel package
- allows `vendor:publish`
- exposes the Artisan command automatically

#### `register(): void`

Responsibilities:

- merges package config into application config
- binds `toon.converter` singleton
- binds `toon` singleton

Developer value:

- centralizes object creation
- makes the package testable and container-friendly
- supports facade usage and app container resolution

### `src/Toon.php`

This is the main public API class.

#### `__construct(ToonConverter $converter, ?ToonDecoder $decoder = null)`

Purpose:

- stores the converter
- creates a decoder if one is not provided
- pulls `coerce_scalar_types` and `escape_style` from config when available

Why it matters:

- keeps config-aware behavior consistent in Laravel and plain PHP usage

#### `convert(mixed $input): string`

Purpose:

- converts input into TOON format

Supported input:

- JSON strings
- arrays
- objects
- scalars

#### `encode(mixed $input): string`

Purpose:

- alias of `convert()`
- improves naming clarity for developers who prefer encode/decode terminology

#### `decode(string $toon): array`

Purpose:

- decodes a TOON string into PHP arrays

#### `estimateTokens(string $toon): array`

Purpose:

- returns a lightweight heuristic estimate of token usage

Returned keys:

- `words`
- `chars`
- `tokens_estimate`

Why it exists:

- useful for quick comparisons between JSON and TOON
- not a model-specific tokenizer, only an estimate

#### `getConfig(string $key, $default = null)`

Purpose:

- safely reads `config('toon.*')` if Laravel config is available
- falls back to defaults outside Laravel

### `src/Converters/ToonConverter.php`

This is the encoding engine and the most important class in the package.

#### `__construct(array $config = [])`

Purpose:

- merges defaults with user config

Default behavior:

- `min_rows_to_tabular` = `2`
- `max_preview_items` = `100`
- `escape_style` = `backslash`

Important note:

- the standalone converter default for `max_preview_items` is `100`
- the published config file sets `200`
- inside Laravel, config normally wins
- outside Laravel, direct converter usage defaults to `100`

#### `toToon(mixed $input): string`

Purpose:

- top-level entry point for encoding

Behavior:

- if input is a JSON-like string, tries to decode it first
- if input is an object, converts it to an array through JSON encode/decode
- if input is an array or traversable, recursively renders TOON
- otherwise converts the scalar to text

#### `valueToToon(mixed $value, int $depth = 0): string`

Purpose:

- recursive renderer for arrays and scalars

Behavior for associative arrays:

- keeps original key order
- emits `key: value` or nested blocks

Behavior for sequential arrays:

- if array is a uniform list of associative arrays, uses compact table mode
- if not, renders each item line-by-line

Behavior for scalars:

- delegates to `inlineScalar()`

#### `arrayOfObjectsToToon(array $arr, int $depth = 0): string`

Purpose:

- converts a uniform list of associative arrays into tabular TOON

Output shape:

```text
items[COUNT]{field1,field2,...}:
  row1col1,row1col2
  row2col1,row2col2
```

Strength:

- this is the main compression feature of the package

Risk:

- only the first `max_preview_items` rows are serialized
- the header still contains the full row count
- decoding such output will only recover the previewed rows, not the full dataset

#### `inlineScalar(mixed $v): string`

Purpose:

- converts scalar-like values into inline TOON-safe text

Behavior:

- `null` becomes empty string
- booleans become `true` or `false`
- ints and floats become stringified numbers
- arrays become comma-separated `key:value` pairs
- strings are normalized and escaped

Important consequence:

- nested arrays inside table cells are flattened into a comma-separated string
- this is not fully reversible during decode

#### `textToToon(string $text): string`

Purpose:

- thin wrapper around `inlineScalar()`

#### `safeKey(string $k): string`

Purpose:

- sanitizes keys for TOON output

Behavior:

- removes characters outside `[A-Za-z0-9_.-]`
- lowercases the key

Important consequence:

- original key casing is lost
- spaces and special characters are removed
- example: `User Name` becomes `username`

#### `isScalar(mixed $v): bool`

Purpose:

- helper to detect `null` or scalar values

#### `looksLikeJson(string $s): bool`

Purpose:

- fast check for JSON-looking strings starting with `{` or `[`

#### `isSequentialArray(array $arr): bool`

Purpose:

- checks whether an array is a list rather than an associative map

#### `isArrayOfUniformObjects(array $arr): bool`

Purpose:

- decides whether a list should be rendered as a table

Requirements:

- row count must meet `min_rows_to_tabular`
- each row must be an array
- each row must have the exact same keys in the same order

### `src/Converters/ToonDecoder.php`

This is the parsing engine for TOON input.

#### `__construct(array $config = [])`

Purpose:

- merges decoder defaults

Defaults:

- `coerce_scalar_types` = `true`
- `escape_style` = `backslash`

#### `fromToon(string $toon): array`

Purpose:

- main parser for TOON strings

What it supports:

- nested key-value blocks based on indentation
- sequential scalar items
- compact tables like `items[2]{id,name}:`
- optional scalar coercion

How it works:

- splits input into lines
- tracks indentation with a stack
- tracks current nested container references
- detects table blocks and collects their rows
- decodes standard `key: value` lines
- treats deeper unmatched lines as sequential items

Important behavior:

- malformed-looking lines are not always rejected
- for example `::: invalid :::` becomes a raw list item, not an exception

#### `finalizeTables(array $node)`

Purpose:

- replaces internal `__table__` markers with clean row arrays

Actual output shape detail:

- a root-level table decodes to an outer array containing the row array as element `0`
- nested tables are also wrapped under their parent arrays
- this means table decode output is not as ergonomic as a plain direct row array

#### `splitCsvEscaped(string $s): array`

Purpose:

- splits a row by commas while respecting backslash-escaped commas

#### `unescape(string $s): string`

Purpose:

- restores escaped sequences like `\\,`, `\\:`, `\\n`, and `\\\\`

#### `coerceValue(string $s): mixed`

Purpose:

- converts textual values to native PHP types when enabled

Supported coercions:

- `true` -> `true`
- `false` -> `false`
- `null` -> `null`
- numeric strings -> `int` or `float`
- empty string -> `null`

### `src/Console/ToonConvertCommand.php`

This is the CLI interface.

Command:

```bash
php artisan toon:convert
```

Supported options:

- optional input file
- `--decode`
- `--encode`
- `--output=`
- `--pretty`
- `--config=`

#### `handle(Filesystem $fs): int`

Responsibilities:

- reads CLI flags
- loads optional config override file
- reads input from a file or STDIN
- decodes TOON to JSON, or encodes JSON/PHP to TOON
- writes output to a file or prints to console
- returns meaningful exit codes for failure cases

Exit code meanings in practice:

- `0` success
- `1` input file or read failure
- `2` config load failure
- `3` `ToonException`
- `4` generic unexpected runtime error
- `5` output write failure

Developer value:

- useful for manual conversion, scripting, and debugging outside controllers

### `src/Facades/Toon.php`

Purpose:

- Laravel facade exposing the `toon` container binding

Main developer benefit:

- enables `Toon::convert()`, `Toon::decode()`, and similar facade-style usage

#### `getFacadeAccessor()`

Returns:

- `'toon'`

### `src/Exceptions/ToonException.php`

Purpose:

- custom exception type for TOON-specific failures

Current implementation:

- marker class extending `RuntimeException`
- no extra logic yet

Important observation:

- the decoder can throw this exception for truly malformed lines
- the converter currently imports `ToonException` but does not use it

## 7. Supported Functionalities Summary

The package currently provides these developer-facing features:

- JSON/PHP array to TOON conversion
- TOON to PHP array decoding
- compact table encoding for uniform object lists
- nested object and nested scalar-list support
- escaping and unescaping of commas, colons, backslashes, and line breaks
- heuristic token estimation
- Laravel facade integration
- Laravel service-provider auto registration
- config publishing
- Artisan-based encode/decode command
- PHPUnit-covered basic integration and edge cases
- GitHub Actions CI workflow

## 8. Real Behavior vs Claimed Behavior

This section is important for a serious technical understanding.

### Works well

- associative array encoding
- basic scalar decoding
- nested associative object reconstruction
- sequential scalar list reconstruction
- uniform object-list table encoding
- type coercion for bool, int, float, and null

### Works, but with caveats

- table decoding works, but returns wrapped array shapes
- key names are preserved in order, but not preserved exactly in spelling
- preview limits reduce output size, but can drop data

### Not fully lossless today

- keys with spaces, symbols, or original case
- nested arrays or objects stored inside tabular row fields
- full round-trip of preview-limited tabular datasets
- malformed TOON validation is looser than documentation implies

## 9. Test Coverage Analysis

The repository has a focused but small PHPUnit suite.

Current test inventory:

- `tests/Converter/ToonConverterTest.php`
  Covers associative arrays, scalar lists, table rendering, escaping, nulls, booleans.
- `tests/Decoder/ToonDecoderTest.php`
  Covers basic decode, table decode, malformed-like input handling, type coercion.
- `tests/Integration/RoundTripTest.php`
  Covers encode/decode interaction.
- `tests/Edge/EdgeCasesTest.php`
  Covers deep nesting, empty arrays, and preview-limit behavior.
- `tests/ToonTest.php`
  Adds extra checks for table output and escaping round-trip.

Observed test status on this machine:

- `15` tests
- `40` assertions
- all passing

## 10. CI and Quality Tooling Analysis

The repo already includes a workflow at `.github/workflows/tests.yml`.

What it currently does:

- runs PHPUnit on PHP 8.1, 8.2, 8.3, and 8.4
- validates `composer.json`
- checks PSR-4 autoloading
- runs security audit
- attempts PHPStan if installed
- attempts PHP-CS-Fixer if installed
- attempts phpbench if installed

Important developer observations:

- CI already exists, so the repository is no longer missing checks
- `phpstan`, `php-cs-fixer`, and `phpbench` are not declared in `composer.json`, so those steps will usually skip
- `composer audit` is non-blocking in workflow because it uses `|| true`

## 11. Security and Dependency Notes

I ran `composer audit` locally.

Current issue found:

- `phpunit/phpunit` has a high-severity advisory in the installed version
- affected installed series: `>=10.0.0,<10.5.62`
- the local package version in lockfile is `10.5.60`

Practical impact:

- this is a dev dependency issue, not the runtime library itself
- it should still be upgraded to at least `10.5.62` or newer within the allowed major version

## 12. Key Limitations and Risks

These are the main technical limitations I found from code review and manual runtime checks.

### 1. `enabled` config is dead configuration

The config advertises a global enable/disable switch, but the code never checks it.

### 2. Key names are mutated

The encoder lowercases keys and strips unsupported characters. This means original keys are not perfectly reversible.

Example:

```php
['User Name' => 'Alice']
```

becomes:

```text
username: Alice
```

### 3. Table preview can silently lose data

If a dataset has more rows than `max_preview_items`, only the preview rows are serialized. Decoding that TOON output will not recover the hidden rows.

### 4. Nested arrays inside table cells are not safely round-tripped

Example row:

```php
['id' => 1, 'meta' => ['x' => 1, 'y' => true]]
```

is flattened into a comma-heavy inline string, and decode only reliably keeps the first matching field slot. This means nested structured values inside table columns are not lossless.

### 5. Table decode shape is awkward

Decoding a root-level table returns an outer wrapper array instead of directly returning the rows.

### 6. Malformed input handling is permissive

Some invalid-looking lines are accepted as list items instead of raising `ToonException`.

### 7. Converter imports `ToonException` but does not use it

This is minor, but indicates either leftover code or planned validation that has not been implemented.

## 13. Developer-Oriented Summary

If I explain this package to a Laravel developer in one paragraph:

TOON is a Laravel package for turning JSON-like data into a smaller, prompt-friendly text format that is easier to send to AI systems. It provides a service class, facade, config, converter, decoder, and CLI command. Its strongest feature is compact tabular encoding for lists of uniform objects. It is good for readability and compression of simple structured data, but today it is not perfectly lossless for all edge cases, especially around key sanitization, preview truncation, and nested structures inside table cells.

## 14. Final Project Summary

This project is a Laravel AI utility package focused on **data compression for prompts**.

Its core value is:

- reducing JSON verbosity
- preserving readable structure
- giving Laravel developers a simple API for encode/decode workflows

Its current maturity level is:

- good as a small, practical package
- understandable architecture
- tested for common scenarios
- still has some correctness gaps between marketing claims and actual round-trip fidelity

If this package is positioned carefully, the most accurate description would be:

> A Laravel package for compact, human-readable serialization of structured data for AI/LLM workflows, with reversible support for many common cases and some important edge-case limitations.

## 15. Recommended Next Improvements

If the goal is production-strength polish, the next best improvements would be:

- enforce or remove the `enabled` config
- make table decoding return a cleaner direct structure
- prevent silent data loss from `max_preview_items`
- preserve keys more faithfully, or document the mutation clearly
- improve nested structured value encoding inside tables
- tighten malformed TOON validation
- add PHPStan, PHP-CS-Fixer, and possibly Pest or higher-depth PHPUnit coverage
- update PHPUnit to a non-vulnerable version
