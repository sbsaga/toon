# TOON for Laravel

### Compact, human-readable, and token-efficient structured data for AI prompts, logs, and Laravel workflows

<p align="center">
  <img src="https://raw.githubusercontent.com/sbsaga/toon/main/assets/logo.webp" alt="TOON logo" width="180">
</p>

<p align="center">
  <a href="https://packagist.org/packages/sbsaga/toon"><img src="https://img.shields.io/packagist/v/sbsaga/toon" alt="Latest version"></a>
  <a href="https://packagist.org/packages/sbsaga/toon"><img src="https://img.shields.io/packagist/dt/sbsaga/toon" alt="Total downloads"></a>
  <a href="https://github.com/sbsaga/toon/actions/workflows/tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/sbsaga/toon/tests.yml?branch=main&label=tests" alt="Tests"></a>
  <a href="https://github.com/sbsaga/toon/blob/main/LICENSE"><img src="https://img.shields.io/github/license/sbsaga/toon" alt="License"></a>
  <img src="https://img.shields.io/badge/Laravel-9%2B-orange" alt="Laravel 9+">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-blue" alt="PHP 8.1+">
</p>

## What This Package Is

TOON is a Laravel package for converting JSON or PHP arrays into a compact text format that stays readable to humans and efficient for AI-oriented workflows.

It is useful when JSON is too noisy for:

- ChatGPT, Gemini, Claude, Mistral, and OpenAI prompt payloads
- log snapshots and debug output
- internal structured storage or fixture review
- repeated API resource collections with the same fields

## Why TOON Helps

JSON repeats a lot of structure. TOON removes much of that repetition while keeping the data understandable.

Example input:

```php
$payload = [
    'project' => 'TOON',
    'users' => [
        ['id' => 1, 'name' => 'Alice', 'active' => true],
        ['id' => 2, 'name' => 'Bob', 'active' => false],
    ],
];
```

Example TOON output:

```text
project: TOON
users:
  items[2]{id,name,active}:
    1,Alice,true
    2,Bob,false
```

## Install in 60 Seconds

```bash
composer require sbsaga/toon
```

Optional config publishing:

```bash
php artisan vendor:publish --provider="Sbsaga\Toon\ToonServiceProvider" --tag=config
```

## Laravel Usage

Facade usage:

```php
use Sbsaga\Toon\Facades\Toon;

$toon = Toon::encode($payload);
$decoded = Toon::decode($toon);
$stats = Toon::estimateTokens($toon);
$diff = Toon::diff($payload);
```

Global helpers:

```php
$toon = toon_encode($payload);
$decoded = toon_decode($toon);
$diff = toon_diff($payload);
```

Collection macro:

```php
$toonRows = collect($payload['users'])->toToon();
```

## Backward Compatibility First

This release keeps `legacy` compatibility mode as the default so existing projects are less likely to break after upgrade.

Default stable API:

- `Toon::convert()`
- `Toon::encode()`
- `Toon::decode()`
- `Toon::estimateTokens()`

New optional improvements:

- `Toon::diff()`
- `Toon::promptBlock()`
- `Toon::validate()`
- `Toon::contentType()`
- `Toon::fileExtension()`
- `toon_encode()`
- `toon_decode()`
- `toon_diff()`
- `toon_prompt()`
- `toon_validate()`
- `Collection::toToon()`
- `Sbsaga\Toon\Concerns\Toonable`
- `strict_mode`
- `delimiter`
- `compatibility_mode`

If you want safer nested round trips and cleaner decode behavior for new work, opt into modern mode:

```php
// config/toon.php
'compatibility_mode' => 'modern',
```

## Reproducible Benchmark

The repository includes a synthetic benchmark fixture and runner:

- [`benchmarks/fixtures/paginated-users.json`](benchmarks/fixtures/paginated-users.json)
- [`benchmarks/run.php`](benchmarks/run.php)

Run it locally:

```bash
php benchmarks/run.php benchmarks/fixtures/paginated-users.json
```

Current result for the included synthetic fixture:

| Metric | JSON | TOON |
| --- | ---: | ---: |
| Characters | 2622 | 1492 |
| Estimated comparison tokens | 656 | 373 |
| Character savings |  | 43.1% |

Notes:

- the fixture data is synthetic and repository-generated
- the token comparison is a lightweight heuristic for relative comparison
- repeated scalar rows usually benefit most from TOON tables

## Why Trust This Package

- Laravel-native facade, service provider, helpers, and collection macro
- backward-safe default compatibility mode for existing users
- opt-in modern mode for cleaner nested-data behavior
- delimiter and strict parsing controls for production use
- benchmark fixture and docs included in the repo
- test coverage for encoder, decoder, helpers, macros, compatibility modes, and edge cases

## Documentation

- [Docs index](docs/README.md)
- [Quickstart](docs/quickstart.md)
- [Format and compatibility](docs/spec-compatibility.md)
- [Syntax cheatsheet](docs/syntax-cheatsheet.md)
- [LLM integration guide](docs/llm-integration.md)
- [Media type and files](docs/media-type-and-files.md)
- [When not to use TOON](docs/when-not-to-use-toon.md)
- [Migration guide](docs/migration.md)
- [Benchmarks](docs/benchmarks.md)
- [Use cases](docs/use-cases.md)
- [FAQ](docs/faq.md)
- [Reference](docs/reference/README.md)
- [Article index](docs/articles/README.md)

## More README Files

- [Benchmarks README](benchmarks/README.md)
- [Examples README](examples/README.md)
- [Articles README](docs/articles/README.md)
- [Reference README](docs/reference/README.md)

## Example Packages and Scenarios

- [AI prompt compression example](examples/laravel-ai-prompt-compression/README.md)
- [Log payload storage example](examples/laravel-log-payload-storage/README.md)
- [LLM response validation example](examples/llm-response-validation/README.md)
- [HTTP TOON response example](examples/http-toon-response/README.md)
- [Eloquent Toonable trait example](examples/eloquent-toonable/README.md)

## Branding Asset

- [Social preview source](assets/social-preview.svg)

## License

Released under the MIT License.
