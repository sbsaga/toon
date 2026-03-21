# Log Payload Storage Example

This example shows how TOON can be used for human-readable structured logging.

## Scenario

You want to capture a checkout payload in a smaller and easier-to-scan format than pretty JSON.

```php
use Illuminate\Support\Facades\Log;
use Sbsaga\Toon\Facades\Toon;

Log::info('checkout payload', [
    'order_id' => $order->id,
    'payload_toon' => Toon::encode([
        'customer' => $customer->only(['id', 'name', 'email']),
        'items' => $items->map->only(['sku', 'qty', 'price'])->all(),
        'totals' => [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'grand_total' => $grandTotal,
        ],
    ]),
]);
```

## Operational Benefit

- easier visual scanning in logs
- smaller structured snapshots for incident review
- reversible data when you need to inspect the original structure again

```php
$decoded = Toon::decode($storedToonPayload);
```
