# HTTP TOON Response Example

This example shows how to return TOON directly from a Laravel endpoint with the conventional media type.

```php
use Illuminate\Support\Facades\Route;
use Sbsaga\Toon\Facades\Toon;

Route::get('/debug/report.toon', function () {
    $payload = [
        'project' => 'TOON',
        'health' => 'ok',
        'users' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ],
    ];

    return response(
        Toon::encode($payload),
        200,
        ['Content-Type' => Toon::contentType()]
    );
});
```

## Useful For

- downloadable debug snapshots
- internal support tools
- human-readable structured exports
