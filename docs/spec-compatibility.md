# Format and Compatibility

## What This Package Optimizes For

This package is a Laravel-first TOON implementation focused on:

- predictable encode/decode behavior for PHP arrays and JSON payloads
- readable output for prompts, logs, and snapshots
- safe round trips for nested data in modern mode
- a stable public API for Laravel applications

## Supported Shapes

The current implementation supports:

- associative arrays as key/value blocks
- scalar lists as indented list values
- uniform scalar object lists as compact TOON tables
- nested maps and nested lists
- root-level and nested tables
- configurable table delimiters
- strict table validation during decode

## Compatibility Modes

### Legacy

Legacy mode is the default so package upgrades remain safer for existing applications.

It preserves:

- lowercased key normalization
- preview-limited tabular output
- legacy inline flattening behavior for nested values in scalar-only positions
- wrapped table structures that older consumers may already expect

### Modern

Modern mode is opt-in and is recommended for new projects or controlled migrations.

It favors:

- full tabular serialization instead of preview truncation
- safer round trips for nested rows
- less aggressive key mutation
- cleaner table decoding

## Delimiters

The package supports `comma`, `pipe`, `tab`, or a raw delimiter character through the `delimiter` config key.

Example:

```php
'delimiter' => 'pipe',
```

## Strict Mode

When `strict_mode` is enabled, decoding throws an exception if:

- a table row has the wrong number of cells
- a table block contains fewer or more rows than declared in the header

## Scope Notes

This release improves TOON compatibility for Laravel application data, but it does not claim universal interoperability with every external TOON dialect or every future spec variant.

If you need stable behavior inside a Laravel codebase, the current package guarantees are:

- tested encode/decode of supported shapes
- documented modern and legacy behavior
- fixture-backed benchmark examples
- explicit migration guidance for changed defaults
