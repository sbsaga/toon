# Media Type and Files

The broader TOON ecosystem uses:

- file extension: `.toon`
- media type: `text/toon`
- encoding: UTF-8

This package exposes those conventions directly:

```php
use Sbsaga\Toon\Facades\Toon;

$contentType = Toon::contentType();   // text/toon; charset=utf-8
$extension = Toon::fileExtension();   // toon
```

## HTTP Response Example

```php
return response(
    Toon::encode($payload),
    200,
    ['Content-Type' => Toon::contentType()]
);
```

## Suggested File Naming

- `report.toon`
- `payload.toon`
- `snapshot.toon`
