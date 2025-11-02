# sbsaga/toon  
TOON converter for Laravel — compact, token‑efficient data format for AI prompts, with full **encode ↔ decode** support.

## Why use it  
When working with large language models (LLMs), prompt size matters. This package helps you:  
- Convert rich data (arrays, JSON, objects) into a simplified “TOON” format to reduce token usage.  
- Decode TOON back into a native PHP array, enabling bidirectional workflows.  
- Estimate approximate token count of your TOON string, giving you insight into cost and usage.  
- Seamlessly integrate into any Laravel (v9 → v12) application via service provider + facade + artisan command.

## Key features  
- ✅ **JSON / PHP → TOON**: Flatten nested data, produce compact representation (`items[n]{a,b}:` style tables)  
- ✅ **TOON → PHP array**: Parse tabular blocks, nested structures, booleans, numbers, backslash‑escaping  
- ✅ **Token estimation**: Quick heuristic for words + chars → estimated tokens  
- ✅ **Laravel ready**: Auto‑discovery, facade `Toon::`, command `php artisan toon:convert`  
- ✅ **CLI usage**: Encode or decode files via artisan or STDIN  
- ✅ **Minimal dependencies**: PHP ≥ 8.1, `illuminate/support` v9‑12

## Installation  
```bash
composer require sbsaga/toon
```

## Usage  

### Laravel facade  
```php
use Sbsaga\Toon\Facades\Toon;

// Convert data to TOON format:
$converted = Toon::convert($data);

// Decode a TOON string back to PHP array:
$decoded = Toon::decode($toonString);

// Estimate tokens:
$stats = Toon::estimateTokens($converted);
```

### Routes example (for testing)  
```php
Route::get('/toon-test', function () {
    $data = [
        'message' => 'Hello, how are you?',
        'user'    => 'Sagar',
        'tasks'   => [
            ['id'=>1,'done'=>false],
            ['id'=>2,'done'=>true],
        ],
    ];

    $converted = Toon::convert($data);
    $decoded   = Toon::decode($converted);
    $stats     = Toon::estimateTokens($converted);
    
    return response()->json([
        'original' => $data,
        'toon'     => $converted,
        'decoded'  => $decoded,
        'stats'    => $stats,
    ]);
});
```

### CLI command  
```bash
# Encode JSON/text file to TOON:
php artisan toon:convert storage/data.json --output=storage/data.toon

# Decode a TOON file:
php artisan toon:convert storage/data.toon --decode --pretty
```

## Coding style & escape rules  
- Scalar values:  
  - Booleans → `true` / `false`  
  - Numbers remain unquoted (if pure numeric)  
  - Strings: whitespace collapsed, commas `,`, colons `:`, newlines `\n`, backslashes `\\` are escaped via backslash style  
- Tabular arrays (homogeneous object lists) are encoded as:  
  ```text
  items[2]{id,done}:
    1,false
    2,true
  ```
  and decoded accordingly.

## Compatibility  
- PHP >= 8.1  
- Laravel packages: `illuminate/support` ^9.0 | ^10.0 | ^11.0 | ^12.0  
- Works as a stand‑alone library as well as a Laravel package

## Versioning  
Following [SemVer](https://semver.org) — backwards‑compatible enhancements will increment minor version; breaking changes will increment major version.

---

## License  
MIT License. See [LICENSE](LICENSE) file for details.
