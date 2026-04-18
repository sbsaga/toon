# Cookbook: Real-World Examples

This page contains practical examples you can copy into Laravel projects.

## 1) API Endpoint: Return TOON as Plain Text

```php
use Illuminate\Support\Facades\Route;
use Sbsaga\Toon\Facades\Toon;

Route::get('/reports/{id}/toon', function (int $id) {
    $payload = [
        'report_id' => $id,
        'status' => 'ready',
        'rows' => [
            ['day' => '2026-04-01', 'sales' => 1200, 'orders' => 17],
            ['day' => '2026-04-02', 'sales' => 980, 'orders' => 12],
        ],
    ];

    $toon = Toon::encode($payload);

    return response($toon, 200)->header('Content-Type', Toon::contentType());
});
```

When to use:

- internal dashboards
- exports for human review
- compact payload previews

## 2) Log Snapshot with Redaction

```php
use Illuminate\Support\Facades\Log;
use Sbsaga\Toon\Facades\Toon;

function logCheckoutPayload(array $payload): void
{
    $safeToon = Toon::encodeWith($payload, function (array $path, string|int|null $key, mixed $value) {
        if (in_array($key, ['card_number', 'cvv', 'token', 'password'], true)) {
            return Toon::skip();
        }

        if ($key === 'email') {
            return '[redacted]';
        }

        return $value;
    });

    Log::info('checkout.payload.toon', ['toon' => $safeToon]);
}
```

## 3) Prompt Context Preparation for LLM Calls

```php
use Sbsaga\Toon\Facades\Toon;

$context = [
    'customer' => [
        'id' => 77,
        'tier' => 'gold',
        'country' => 'US',
    ],
    'recent_orders' => [
        ['id' => 501, 'total' => 129.50, 'status' => 'delivered'],
        ['id' => 502, 'total' => 49.99, 'status' => 'processing'],
    ],
];

$toonContext = Toon::encodeWith($context, function (array $path, string|int|null $key, mixed $value) {
    return $key === 'email' ? '[redacted]' : $value;
});

$prompt = "Use the context below to suggest next best action.\n\n"
    . Toon::promptBlock(toon_decode($toonContext));
```

## 4) Validate Imported TOON Before Processing

```php
use Sbsaga\Toon\Facades\Toon;

$incomingToon = file_get_contents(storage_path('app/imports/inventory.toon'));
$validation = Toon::validate($incomingToon, true);

if (!$validation['valid']) {
    throw new RuntimeException('Invalid TOON import: ' . $validation['error']);
}

$data = Toon::decode($incomingToon);
```

Use strict validation for:

- fixture ingestion
- batch import jobs
- any untrusted input

## 5) Line-Based Pipeline

```php
use Sbsaga\Toon\Facades\Toon;

$lines = Toon::encodeLines($payload);

foreach ($lines as $line) {
    // send line to stream/socket/file writer
}

$decoded = Toon::decodeFromLines($lines);
```

For helper-style code:

```php
$lineArray = toon_encode_lines($payload);
```

## 6) CLI Data Conversion in Team Workflows

Convert JSON fixture to TOON:

```bash
php artisan toon:convert tests/fixtures/users.json --encode --output=tests/fixtures/users.toon
```

Decode TOON fixture to pretty JSON:

```bash
php artisan toon:convert tests/fixtures/users.toon --decode --pretty --output=tests/fixtures/users.pretty.json
```

Use explicit from/to with stats:

```bash
php artisan toon:convert storage/app/payload.data --from=json --to=toon --stats
```

## 7) Compatibility-Safe Migration Path

```php
// Step 1: keep legacy behavior
config(['toon.compatibility_mode' => 'legacy']);

// Step 2: compare output in staging
$legacy = toon_encode($payload);
config(['toon.compatibility_mode' => 'modern']);
$modern = toon_encode($payload);

// Step 3: inspect differences before switching defaults
```

## 8) Controller + Resource Pattern

```php
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Sbsaga\Toon\Facades\Toon;

public function showToon(int $id)
{
    $order = Order::query()->findOrFail($id);
    $payload = (new OrderResource($order))->resolve();

    return response(Toon::encode($payload), 200)
        ->header('Content-Type', Toon::contentType());
}
```

## 9) Decision Matrix

Use TOON when:

- repeated object structures create JSON noise
- humans need to review structured snapshots quickly
- token budgets matter for prompt context

Use JSON when:

- payload is public API transport
- interoperability with external systems is primary goal
- strict schema tooling around JSON is already in place

## 10) Minimal Best-Practice Template

```php
use Sbsaga\Toon\Facades\Toon;

function encodeForInternalUse(array $payload): string
{
    return Toon::encodeWith($payload, function (array $path, string|int|null $key, mixed $value) {
        if (in_array($key, ['password', 'token', 'secret'], true)) {
            return Toon::skip();
        }

        return $value;
    });
}
```

This single pattern gives you:

- safer logs/prompts
- backward-compatible runtime defaults
- easy future migration to modern mode when ready
