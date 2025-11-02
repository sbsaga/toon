# 🧠 TOON for Laravel  
### Compact · Token-Efficient · Human-Readable Data Format for AI Prompts & LLM Contexts

<p align="center">
  <a href="https://packagist.org/packages/sbsaga/toon">
    <img src="https://img.shields.io/packagist/v/sbsaga/toon.svg?style=for-the-badge&color=blueviolet" alt="Latest Version on Packagist">
  </a>
  <a href="https://packagist.org/packages/sbsaga/toon">
    <img src="https://img.shields.io/packagist/dt/sbsaga/toon.svg?style=for-the-badge&color=brightgreen" alt="Total Downloads">
  </a>
  <img src="https://img.shields.io/badge/license-MIT-green?style=for-the-badge" alt="License: MIT">
  <img src="https://img.shields.io/badge/Laravel-9%2B-orange?style=for-the-badge&logo=laravel" alt="Laravel 9+">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-blue?style=for-the-badge&logo=php" alt="PHP 8.1+">
</p>

---

## ✨ Overview

**TOON** is a Laravel package that converts complex JSON or PHP arrays into a **compact, human-readable, token-efficient format** — ideal for **AI prompts**, **LLM context preprocessing**, and **debugging structured data**.

Reduce token usage while maintaining structure clarity — perfect for prompt optimization workflows.

---

## 🚀 Installation

```bash
composer require sbsaga/toon
```

> Laravel’s auto-discovery automatically registers the service provider and facade.

---

## ⚙️ Configuration

Publish configuration (optional):

```bash
php artisan vendor:publish --provider="Sbsaga\Toon\ToonServiceProvider" --tag=config
```

Creates `config/toon.php`:

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

**Output:**
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

Convert or decode directly from terminal:

```bash
php artisan toon:convert storage/test.json
php artisan toon:convert storage/test.toon --decode --pretty
```

Or specify output:
```bash
php artisan toon:convert storage/test.json --output=storage/result.toon
```

---

## 🧪 Quick Test Route

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
| LLM context limited | Reduces token count before model input |
| Hard to read nested JSON | Converts into structured readable format |
| Need reversibility | Supports TOON → JSON decoding |

---

## 🧰 Compatibility

| Laravel | PHP | Package |
|----------|-----|----------|
| 9.x – 12.x | ≥ 8.1 | v1.0.8+ |

---

## 📜 License

Licensed under the **MIT License** — free for personal & commercial use.

---

> 🧠 *“Compress your prompts, not your ideas.” — TOON helps you talk to AI efficiently.*
# 🧠 TOON for Laravel  
### Compact · Token-Efficient · Human-Readable Data Format for AI Prompts & LLM Contexts

<p align="center">
  <a href="https://packagist.org/packages/sbsaga/toon">
    <img src="https://img.shields.io/packagist/v/sbsaga/toon.svg?style=for-the-badge&color=blueviolet" alt="Latest Version on Packagist">
  </a>
  <a href="https://packagist.org/packages/sbsaga/toon">
    <img src="https://img.shields.io/packagist/dt/sbsaga/toon.svg?style=for-the-badge&color=brightgreen" alt="Total Downloads">
  </a>
  <img src="https://img.shields.io/badge/license-MIT-green?style=for-the-badge" alt="License: MIT">
  <img src="https://img.shields.io/badge/Laravel-9%2B-orange?style=for-the-badge&logo=laravel" alt="Laravel 9+">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-blue?style=for-the-badge&logo=php" alt="PHP 8.1+">
</p>

---

## ✨ Overview  

**TOON** (Token-Optimized Object Notation) is a Laravel package that transforms complex JSON or PHP arrays into a **compact, readable, and token-efficient representation** — ideal for **AI/LLM context preprocessing**, **prompt compression**, **structured data exchange**, and **human-friendly debugging**.  

It’s designed for developers who need to **reduce token costs**, **speed up AI inference**, and **keep human-readable structure** intact.  

---

## 🚀 Key Features  

- 🧩 **JSON ⇄ TOON reversible conversion**  
- 📉 **Reduce token and character usage by 60–80%**  
- 🤖 **Optimized for AI prompt engineering (ChatGPT, Claude, Gemini, etc.)**  
- 🔍 **Readable tabular format for collections**  
- ⚙️ **Laravel-native integration** with Facade, Command, and Config  
- 📊 **Token analytics** — measure compression, tokens, and characters  
- 🧠 **Key order preservation** — consistent structure in output  

---

## 🧪 Real-World Benchmark  

**Dataset:** 20 structured user objects (names, jobs, locations, etc.)  

| Metric | JSON | TOON | Reduction |
|---------|------|------|-----------|
| Size (bytes) | 7,718 | 2,538 | **67.12% smaller** |
| Characters | 7,718 | 2,538 | **67.12% fewer characters** |
| Approx. Tokens (gpt-4-turbo) | 1,930 | 640 | **~66.8% fewer tokens** |

**Result:**  
> 🧠 TOON format saves ~65–70% tokens while keeping every data point human-readable.  

---

## 💡 Why TOON?  

| Problem | Traditional JSON | TOON Advantage |
|----------|------------------|----------------|
| Large LLM context window usage | Uses many tokens for keys & braces | Compresses structure into compact tabular form |
| Difficult to scan nested structures | Hard to visually parse deeply nested data | Indented YAML-like layout with clear hierarchy |
| Not reversible easily when compressed | Compression destroys semantics | 100% reversible TOON ⇄ JSON conversion |
| Lacks AI prompt optimization | Tokens wasted on syntax | Optimized for minimal token count |

---

## ⚙️ Installation  

```bash
composer require sbsaga/toon
```

> Laravel auto-discovers the service provider and facade.  

---

## 🧩 Configuration  

Publish configuration (optional):  

```bash
php artisan vendor:publish --provider="Sbsaga\Toon\ToonServiceProvider" --tag=config
```

**Creates:** `config/toon.php`  

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

**Output:**

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

## 🧪 Quick Benchmark Route  

```php
use Illuminate\Support\Facades\Route;
use Sbsaga\Toon\Facades\Toon;

Route::get('/toon-benchmark', function () {
    $json = json_decode(file_get_contents(storage_path('app/users.json')), true);

    $jsonEncoded = json_encode($json, JSON_PRETTY_PRINT);
    $toonEncoded = Toon::convert($json);

    return response()->json([
        'json_size_bytes' => strlen($jsonEncoded),
        'toon_size_bytes' => strlen($toonEncoded),
        'saving_percent' => round(100 - (strlen($toonEncoded) / strlen($jsonEncoded) * 100), 2),
        'json_content' => $jsonEncoded,
        'toon_content' => $toonEncoded,
    ]);
});
```

---

## 🧮 Analytics & Visualization  

| Metric | Description | Example Value |
|--------|--------------|---------------|
| `json_size_bytes` | Original JSON byte size | 7,718 |
| `toon_size_bytes` | Compressed TOON byte size | 2,538 |
| `saving_percent` | % space reduction | **67.12%** |
| `tokens_estimate` | Approx LLM token count | 640 |
| `compression_ratio` | Toon/JSON size ratio | 0.33 |

> ⚡ **TOON saves up to 70% tokens** — resulting in cheaper API calls and more data per prompt.

---

## 🧰 Artisan Commands  

Convert or decode directly from the CLI:  

```bash
php artisan toon:convert storage/test.json
php artisan toon:convert storage/test.toon --decode --pretty
```

Or specify output:  

```bash
php artisan toon:convert storage/test.json --output=storage/result.toon
```

---

## 📦 Integration Use Cases  

- 🤖 **Prompt Engineering Pipelines** — compress JSON data for OpenAI, Anthropic, or Gemini inputs.  
- 📉 **AI Token Optimization** — reduce cost of structured prompts by up to 70%.  
- 🧠 **LLM Data Contextualization** — feed compact tabular data into long-context models.  
- 🧾 **Readable Debugging** — replace raw JSON dumps in logs with compact TOON format.  
- 🔍 **Data Preview Tools** — display large collections in compact human-readable syntax.  

---

## 🧰 Compatibility  

| Laravel | PHP | Package |
|----------|-----|----------|
| 9.x – 12.x | ≥ 8.1 | v1.1.0+ |

---

## 📊 Example Compression Visualization  

```
JSON (7.7 KB)
██████████████████████████████████████████████████████████████████████████

TOON (2.5 KB)
█████████████████
```
🧠 **~67% space saved**, same information retained.

---

## 📜 License  

Licensed under the **MIT License** — free for personal & commercial use.

---

> 🧠 *“Compress your prompts, not your ideas.” — TOON helps you talk to AI efficiently.*
