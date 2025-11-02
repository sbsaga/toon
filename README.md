# 🧠 TOON for Laravel  
### Compact, Token-Efficient Data Format for AI Prompts & LLM Contexts

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sbsaga/toon.svg?style=flat-square)](https://packagist.org/packages/sbsaga/toon)
[![Total Downloads](https://img.shields.io/packagist/dt/sbsaga/toon.svg?style=flat-square)](https://packagist.org/packages/sbsaga/toon)
[![License](https://img.shields.io/github/license/sbsaga/toon?style=flat-square)](LICENSE)
![Laravel](https://img.shields.io/badge/Laravel-9%2B-orange?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue?style=flat-square&logo=php)

---

### ✨ Overview

**TOON** is a Laravel package that converts complex JSON or PHP arrays into a **compact, human-readable, token-efficient format** — ideal for **AI prompts**, **LLM preprocessing**, or **debugging structured data**.

It helps reduce token usage while preserving data structure clarity.

---

## 🚀 Installation

```bash
composer require sbsaga/toon
```

> Laravel’s auto-discovery automatically registers the service provider and facade.

---

## ⚙️ Configuration (Optional)

Publish the config file if you want to tweak behavior:

```bash
php artisan vendor:publish --provider="Sbsaga\Toon\ToonServiceProvider" --tag=config
```

This creates `config/toon.php`:

```php
return [
    'enabled' => true,
    'escape_style' => 'backslash',
    'min_rows_to_tabular' => 2,
    'max_preview_items' => 200,
];
```

---

## 🧠 Usage

Use the **`Toon` facade** to convert data between JSON and TOON format.

### ➤ Convert JSON → TOON

```php
use Sbsaga\Toon\Facades\Toon;

$data = [
    'user' => 'Sagar',
    'message' => 'Hello, how are you?',
    'tasks' => [
        ['id' => 1, 'done' => false],
        ['id' => 2, 'done' => true],
    ],
];

$converted = Toon::convert($data);

echo $converted;
```

**Output (TOON format):**
```
user: Sagar
message: Hello\, how are you?
tasks:
  items[2]{done,id}:
    false,1
    true,2
```

---

### ➤ Convert TOON → JSON

```php
$toonString = <<<TOON
user: Sagar
tasks:
  items[2]{id,done}:
    1,false
    2,true
TOON;

$json = Toon::decode($toonString);

print_r($json);
```

---

### ➤ Estimate Tokens

```php
$stats = Toon::estimateTokens($converted);

print_r($stats);
```

Output:
```json
{
  "words": 20,
  "chars": 182,
  "tokens_estimate": 19
}
```

---

## 🧩 Artisan Command

You can convert or decode files directly from the command line:

```bash
php artisan toon:convert storage/test.json
php artisan toon:convert storage/test.toon --decode --pretty
```

You can also specify output:

```bash
php artisan toon:convert storage/test.json --output=storage/result.toon
```

---

## 🧪 Testing Routes (Optional)

Add this to `routes/web.php` to verify conversions interactively:

```php
use Illuminate\Support\Facades\Route;
use Sbsaga\Toon\Facades\Toon;

Route::get('/toon-test', function () {
    $data = [
        'message' => 'Hello, how are you?',
        'user' => 'Sagar',
        'tasks' => [
            ['id' => 1, 'done' => false],
            ['id' => 2, 'done' => true],
        ],
    ];

    $converted = Toon::convert($data);
    $reverse = Toon::decode($converted);

    return response()->json([
        'original_json' => $data,
        'converted_toon' => $converted,
        'reverse_json' => $reverse,
        'token_stats' => Toon::estimateTokens($converted),
    ]);
});
```

---

## 💡 Why TOON?

| Problem | TOON Solution |
|----------|----------------|
| JSON is verbose | Converts to a compact token-efficient format |
| LLM context limited | Reduces token count before sending to GPT-style models |
| Hard to read nested JSON | Converts into readable, indented key-value blocks |
| Reversible | Supports decoding TOON → JSON |

---

## 🧰 Supported Versions

| Laravel | PHP | Package |
|----------|-----|----------|
| 9.x – 12.x | ≥ 8.1 | v1.0.8+ |

---

## 📜 License

This package is open-source software licensed under the [MIT License](LICENSE).

---

### 🔗 Links
- **GitHub:** [https://github.com/sbsaga/toon](https://github.com/sbsaga/toon)
- **Packagist:** [https://packagist.org/packages/sbsaga/toon](https://packagist.org/packages/sbsaga/toon)
- **Author:** [Sagar S. Bhedodkar](https://github.com/sbsaga)

---

> 🧠 *“Compress your prompts, not your ideas.” — TOON helps you talk to AI efficiently.*
