# Introducing TOON for Laravel

When structured data moves from APIs into prompts, logs, or debug snapshots, JSON often carries more syntax than signal. Repeated keys, quotes, and braces are helpful for machines, but they can become expensive and noisy in the places where developers and language models need to read quickly.

That is the problem TOON is built to solve.

TOON, short for Token-Optimized Object Notation, is a compact text format for structured data. In Laravel projects it gives you a readable alternative for internal workflows where payload size and scanability both matter.

## Why I Built It

The original motivation was practical:

- AI prompts were getting too large
- log snapshots were harder to scan than they should be
- repeated API resource keys were taking space without adding meaning

I wanted something that still felt natural in Laravel applications and could be decoded back safely when needed.

## What TOON Does Well

TOON works especially well with repeated structured rows. Instead of repeating every key on every object, TOON can promote the shared field list into a single header and render the values as rows.

Example PHP data:

```php
$payload = [
    'users' => [
        ['id' => 1, 'name' => 'Alice', 'active' => true],
        ['id' => 2, 'name' => 'Bob', 'active' => false],
    ],
];
```

TOON output:

```text
users:
  items[2]{id,name,active}:
    1,Alice,true
    2,Bob,false
```

That structure is easier to scan than raw JSON, and it also reduces repetition.

## Laravel-First Usage

The package is designed to feel familiar inside Laravel.

```php
use Sbsaga\Toon\Facades\Toon;

$toon = Toon::encode($payload);
$decoded = Toon::decode($toon);
$diff = Toon::diff($payload);
```

There are helpers too:

```php
$toon = toon_encode($payload);
$decoded = toon_decode($toon);
```

And collections can be encoded directly:

```php
$toon = collect($payload['users'])->toToon();
```

## What Changed in the Current Release

The package now leans harder into trust and predictability:

- root-level tables decode to plain row lists
- nested row data stays expanded instead of being flattened into lossy table cells
- strict mode can validate malformed tables
- modern and legacy compatibility modes make upgrade behavior explicit
- benchmark fixtures and migration docs now live in the repository

## Where TOON Fits Best

TOON is not a replacement for JSON everywhere. It is most useful in:

- AI prompt construction
- compact log payload storage
- fixture and snapshot review
- debugging repeated resource collections
- internal tools where humans read the serialized output

That is the sweet spot: smaller structured payloads without giving up readability.
