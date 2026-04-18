# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Added

- global helpers: `toon_encode()`, `toon_decode()`, and `toon_diff()`
- global helpers: `toon_prompt()` and `toon_validate()`
- `Collection::toToon()` macro
- fixture-backed benchmark assets and documentation
- quickstart, compatibility, migration, FAQ, and use-case docs
- LLM integration, syntax cheatsheet, media-type, and usage-boundary docs
- prompt block, validation, content type, and file extension utilities
- opt-in `Toonable` trait for models and DTOs
- additional examples, issue templates, and a social preview asset
- release template, contributing guide, and security policy

### Changed

- tightened package metadata and README positioning
- expanded `illuminate/support` compatibility to include Laravel 13 (`^13.0`)
- added `diff()` metrics for JSON-vs-TOON comparisons
- added delimiter, strict mode, and compatibility mode support
- improved nested round-trip safety in modern mode
- root-level tables now decode to plain row lists

### Tests

- expanded coverage for helpers, macros, delimiter handling, strict decoding, and nested round trips
