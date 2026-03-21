# Use Cases

## AI Prompt Compression

TOON is useful when you want to send structured business data into LLM prompts without carrying full JSON punctuation overhead.

```php
$context = [
    'customer' => $customer->only(['id', 'name', 'tier']),
    'orders' => $orders->map->only(['id', 'status', 'total'])->all(),
];

$prompt = "Review this customer context:\n" . toon_encode($context);
```

## Log Payload Storage

TOON works well for human-readable snapshots of request payloads, webhook bodies, or job context.

```php
Log::info('checkout payload', [
    'toon' => toon_encode($payload),
]);
```

## Debug Snapshots

Instead of storing pretty JSON in long-lived debug artifacts, TOON can give you a smaller text snapshot that is still reviewable in a diff.

## Background Jobs

When a job needs a compact serialized representation for observability or retry diagnostics, TOON is often easier to scan than raw JSON.

## API Fixture Compression

Uniform API resources, especially paginator responses, benefit from TOON's tabular rendering because repeated field names appear once in the header instead of once per row.
