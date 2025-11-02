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

## 📚 Table of Contents  
1. [Overview](#-overview)  
2. [Key Features](#-key-features)  
3. [Benchmark & Analytics](#-real-world-benchmark)  
4. [Installation](#-installation)  
5. [Configuration](#-configuration)  
6. [Usage](#-usage)  
   - [Convert JSON → TOON](#-convert-json--toon)  
   - [Convert TOON → JSON](#-convert-toon--json)  
   - [Estimate Tokens](#-estimate-tokens)  
7. [Quick Benchmark Route](#-quick-benchmark-route)  
8. [Analytics & Visualization](#-analytics--visualization)  
9. [CLI Commands](#-artisan-commands)  
10. [Integration Use Cases](#-integration-use-cases)  
11. [Compatibility](#-compatibility)  
12. [Compression Visualization](#-example-compression-visualization)  
13. [License](#-license)  

---

## ✨ Overview  

**TOON** (Token-Optimized Object Notation) transforms complex JSON or PHP arrays into a **compact, human-readable, and token-efficient format** — perfect for **AI prompts**, **LLM context preprocessing**, and **structured debugging**.  

It’s designed for developers working with **ChatGPT, Claude, Gemini, or OpenAI APIs** to **save tokens, cost, and context space** while keeping human readability intact.  

---

## 🚀 Key Features  

| Feature | Description |
|----------|-------------|
| 🔁 **Bidirectional Conversion** | Seamlessly convert JSON ⇄ TOON |
| 🧩 **Readable & Compact** | Structured, YAML-like output |
| 💰 **Token-Efficient** | Save up to 70% token usage |
| ⚙️ **Laravel Integrated** | Facade, Artisan command, and config support |
| 🔒 **Key Order Preservation** | Keeps field order consistent |
| 📊 **Analytics Support** | Token, byte, and character metrics |
| 🌍 **AI-Ready** | Perfect for prompt engineering workflows |

---

## 🧪 Real-World Benchmark  

**Dataset:** 20 structured user records with 12 keys each.  

| Metric | JSON | TOON | Reduction |
|---------|------|------|-----------|
| Size (bytes) | 7,718 | 2,538 | **67.12% smaller** |
| Tokens (est.) | 1,930 | 640 | **~66.8% fewer tokens** |

> 🧠 TOON consistently reduces token count by **60–75%**, optimizing AI context efficiency.  

---

## ⚙️ Installation  

```bash
composer require sbsaga/toon
```

> Laravel auto-discovers the service provider and facade.  

---

## ⚙️ Configuration  

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

## 📊 Analytics & Visualization  

| Metric | Description | Example |
|--------|--------------|---------|
| `json_size_bytes` | Original JSON byte size | 7,718 |
| `toon_size_bytes` | Compressed TOON byte size | 2,538 |
| `saving_percent` | % space saved | 67.12% |
| `tokens_estimate` | Approx token count | 640 |
| `compression_ratio` | Toon/JSON size ratio | 0.33 |

> ⚡ **TOON reduces tokens by up to 70%**, lowering API cost and improving throughput.

---

## 🧰 Artisan Commands  

Convert or decode directly from CLI:  

```bash
php artisan toon:convert storage/test.json
php artisan toon:convert storage/test.toon --decode --pretty
```

Specify output file:  

```bash
php artisan toon:convert storage/test.json --output=storage/result.toon
```

---

## 🧩 Integration Use Cases  

| Use Case | Benefit |
|-----------|----------|
| 🤖 AI Prompt Engineering | Compress structured context for LLMs |
| 📉 Token Optimization | Reduce cost of OpenAI / Anthropic calls |
| 🧠 Data Preprocessing | Simplify JSON input for models |
| 🧾 Logging & Debugging | More readable than raw JSON dumps |
| 🔍 Developer Tools | Compact previews in UI or CLI tools |

---

## 🧰 Compatibility  

| Laravel | PHP | Package Version |
|----------|-----|----------------|
| 9.x – 12.x | ≥ 8.1 | v1.1.0+ |

---

## 📉 Example Compression Visualization  

```
JSON (7.7 KB)
██████████████████████████████████████████████████████████████████████████

TOON (2.5 KB)
█████████████████
```

🧠 **~67% reduction** with complete reversibility.

---

## 💡 Contribution  

Contributions are welcome!  

1. Fork the repository  
2. Create a feature branch (`git checkout -b feature/your-feature`)  
3. Commit your changes (`git commit -m "Add new feature"`)  
4. Push and open a Pull Request  

---

## 📜 License  

Licensed under the **MIT License** — free for personal and commercial use.  

---

> 🧠 *“Compress your prompts, not your ideas.” — TOON helps you talk to AI efficiently.*  
