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

<p align="center">
  <img src="docs/assets/images/hero_banner.png" alt="TOON for Laravel — Compact, token-efficient data for AI workflows" width="100%">
</p>

---

## What This Package Is

TOON is a Laravel package for converting JSON or PHP arrays into a compact text format that stays readable to humans and efficient for AI-oriented workflows.

It is useful when JSON is too noisy for:

- ChatGPT, Gemini, Claude, Mistral, and OpenAI prompt payloads
- log snapshots and debug output
- internal structured storage or fixture review
- repeated API resource collections with the same fields

## Why TOON Helps

JSON repeats a lot of structure. TOON removes much of that repetition while keeping the data understandable.

<p align="center">
  <img src="docs/assets/images/json_vs_toon_comparison.png" alt="JSON vs TOON — 43.1% character savings" width="700">
</p>

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

## Core Features

<p align="center">
  <img src="docs/assets/images/feature_overview.png" alt="TOON Core Features — Token Efficient, Round-Trip Safe, Replacer API, Streaming, CLI Tools, LLM-Ready" width="700">
</p>

## Install in 60 Seconds

```bash
composer require sbsaga/toon
```

Optional config publishing:

```bash
php artisan vendor:publish --provider="Sbsaga\Toon\ToonServiceProvider" --tag=config
```

## How It Works

<p align="center">
  <img src="docs/assets/images/encode_decode_flow.png" alt="TOON Encode/Decode Pipeline — PHP Array → Toon::encode() → TOON String → Toon::decode() → PHP Array" width="700">
</p>

## Laravel Usage

Facade usage:

```php
use Sbsaga\Toon\Facades\Toon;

$toon = Toon::encode($payload);
$decoded = Toon::decode($toon);
$stats = Toon::estimateTokens($toon);
$diff = Toon::diff($payload);
```

Replacer usage:

```php
use Sbsaga\Toon\Facades\Toon;

$toon = Toon::encodeWith($payload, function (array $path, string|int|null $key, mixed $value) {
    if ($key === 'debug') {
        return Toon::skip();
    }

    if ($key === 'email') {
        return '[redacted]';
    }

    return $value;
});
```

Global helpers:

```php
$toon = toon_encode($payload);
$toonFiltered = toon_encode_with($payload, fn (array $path, string|int|null $key, mixed $value) => $value);
$decoded = toon_decode($toon);
$diff = toon_diff($payload);
```

Collection macro:

```php
$toonRows = collect($payload['users'])->toToon();
```

Streaming convenience:

```php
$lines = Toon::encodeLines($payload); // Generator<string>
$decoded = Toon::decodeFromLines($lines);
```

CLI conversion (advanced options):

```bash
php artisan toon:convert storage/example.json --from=json --to=toon --stats
php artisan toon:convert storage/example.toon --decode --strict --pretty
php artisan toon:convert storage/example.json --mode=modern --delimiter=pipe --output=storage/example.toon
```

## Laravel Integration Architecture

<p align="center">
  <img src="docs/assets/images/laravel_integration_architecture.png" alt="TOON Laravel Integration — ServiceProvider, Facade, Encoder, Decoder, Config" width="700">
</p>

## Backward Compatibility First

This release keeps `legacy` compatibility mode as the default so existing projects are less likely to break after upgrade.

<p align="center">
  <img src="docs/assets/images/compatibility_modes.png" alt="Legacy vs Modern Mode — Choose your compatibility path" width="700">
</p>

Default stable API:

- `Toon::convert()`
- `Toon::encode()`
- `Toon::decode()`
- `Toon::estimateTokens()`

New optional improvements:

- `Toon::diff()`
- `Toon::convertWith()`
- `Toon::encodeWith()`
- `Toon::skip()`
- `Toon::encodeLines()`
- `Toon::decodeFromLines()`
- `Toon::promptBlock()`
- `Toon::validate()`
- `Toon::contentType()`
- `Toon::fileExtension()`
- `toon_encode()`
- `toon_encode_with()`
- `toon_encode_lines()`
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

<p align="center">
  <img src="docs/assets/images/token_savings_chart.png" alt="TOON Benchmark — 43.1% character and token savings vs JSON" width="700">
</p>

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

New here: start with [Quickstart](docs/quickstart.md), then [Cookbook: real-world examples](docs/cookbook.md).

Shipping to production: read [Production playbook](docs/production-playbook.md), then [Migration guide](docs/migration.md), and keep [Troubleshooting](docs/troubleshooting.md) handy.

- [Docs index](docs/README.md)
- [Quickstart](docs/quickstart.md)
- [Cookbook: real-world examples](docs/cookbook.md)
- [Production playbook](docs/production-playbook.md)
- [Upgrade safety for v1.3.0](docs/upgrade-safety-v1-3.md)
- [CLI conversion guide](docs/cli-conversion-guide.md)
- [Replacer recipes](docs/replacer-recipes.md)
- [Format and compatibility](docs/spec-compatibility.md)
- [Syntax cheatsheet](docs/syntax-cheatsheet.md)
- [LLM integration guide](docs/llm-integration.md)
- [Media type and files](docs/media-type-and-files.md)
- [When not to use TOON](docs/when-not-to-use-toon.md)
- [Migration guide](docs/migration.md)
- [Benchmarks](docs/benchmarks.md)
- [Use cases](docs/use-cases.md)
- [FAQ](docs/faq.md)
- [Troubleshooting](docs/troubleshooting.md)
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
