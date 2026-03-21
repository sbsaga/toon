# Eloquent Toonable Trait Example

This example shows an opt-in trait for models or DTOs that want direct TOON convenience methods.

```php
use Illuminate\Database\Eloquent\Model;
use Sbsaga\Toon\Concerns\Toonable;

class ReportSnapshot extends Model
{
    use Toonable;
}

$snapshot = ReportSnapshot::findOrFail(1);

$toon = $snapshot->toToon();
$prompt = $snapshot->toToonPrompt();
```

## Why It Helps

- gives Laravel users an easy object-level entry point
- stays opt-in, so existing applications are unaffected
- works well for Eloquent models, DTOs, and value objects with `toArray()`
