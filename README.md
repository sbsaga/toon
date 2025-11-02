# 🧠 TOON for Laravel  
### Compact · Token-Efficient · Human-Readable Data Format for AI Prompts & LLM Contexts  

---

<p align="center">
  <img src="https://img.shields.io/packagist/v/sbsaga/toon.svg?style=flat-square" alt="Latest Version">
  <img src="https://img.shields.io/packagist/dt/sbsaga/toon.svg?style=flat-square" alt="Downloads">
  <img src="https://img.shields.io/github/license/sbsaga/toon?style=flat-square" alt="License">
  <img src="https://img.shields.io/badge/Laravel-9%2B-orange?style=flat-square&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-blue?style=flat-square&logo=php" alt="PHP">
</p>

---

## ✨ Overview  

**TOON** is a Laravel package that converts complex arrays or JSON data into a **compact, token-efficient, human-readable format** — ideal for **AI prompts**, **LLM preprocessing**, and **structured debugging**.  

It helps reduce token usage while keeping data structure clarity — saving you tokens and improving prompt interpretability.

---

## ⚙️ Installation  

```bash
composer require sbsaga/toon
```

Laravel’s auto-discovery automatically registers the service provider and facade.

---

## 🔧 Optional Configuration  

You can publish the configuration file to customize TOON behavior:

```bash
php artisan vendor:publish --provider="Sbsaga\Toon\ToonServiceProvider" --tag=config
```

**`config/toon.php`:**

```php
return [
    'enabled' => true,
    'escape_style' => 'backslash',
    'min_rows_to_tabular' => 2,
    'max_preview_items' => 200,
];
```

---

## 🧠 Usage Examples  

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
$toon = <<<TOON
user: Sagar
tasks:
  items[2]{id,done}:
    1,false
    2,true
TOON;

$json = Toon::decode($toon);

print_r($json);
```

---

### ➤ Estimate Tokens  

```php
$stats = Toon::estimateTokens($converted);

print_r($stats);
```

**Output:**
```json
{
  "words": 20,
  "chars": 182,
  "tokens_estimate": 19
}
```

---

## 🧰 Artisan CLI  

Convert or decode directly from the command line:  

```bash
php artisan toon:convert storage/test.json
php artisan toon:convert storage/test.toon --decode --pretty
```

Save the result to a file:  

```bash
php artisan toon:convert storage/test.json --output=storage/result.toon
```

---

## 🧪 Quick Web Test (Optional)  

Add this route in `routes/web.php` for instant browser testing:  

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

Then visit:  
🔗 `http://your-app.test/toon-test`

---

## 💡 Why Choose TOON  

| Problem | TOON Advantage |
|----------|----------------|
| JSON is verbose | Converts to a clean, compact, token-efficient text format |
| LLM context is limited | Minimizes token usage for long structured prompts |
| Hard-to-read nested JSON | Produces human-friendly indented structure |
| Hard to reverse engineer | Easily decoded back to JSON |

---

## 🧩 Compatibility  

| Laravel | PHP | TOON Version |
|----------|-----|--------------|
| 9.x – 12.x | ≥ 8.1 | v1.0.8+ |

---

## 📜 License  

Released under the **MIT License** — free for commercial and open-source use.

---

<p align="center">
  <b>🧠 Compress your prompts, not your ideas.</b><br>
  <sub>TOON helps you talk to AI efficiently — by making data beautifully minimal.</sub>
</p>
