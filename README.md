# 🧠 TOON for Laravel  
### Compact · Token-Efficient · Human-Readable Data Format for AI Prompts & LLM Contexts  

<p align="center">
  <img src="https://raw.githubusercontent.com/sbsaga/toon/main/assets/logo.webp" alt="TOON Logo" width="180">
</p>

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
  <img src="https://img.shields.io/badge/AI%20Ready-ChatGPT%2C%20Gemini%2C%20Claude%2C%20OpenAI-success?style=for-the-badge&logo=openai" alt="AI Ready">
</p>

---

## 📚 Table of Contents  
1. [Overview](#-overview)  
2. [Key Features](#-key-features)  
3. [Benchmark & Analytics](#-real-world-benchmark)  
4. [Installation](#-installation)  
5. [Configuration](#-configuration)  
6. [Usage](#-usage)  
7. [Quick Benchmark Route](#-quick-benchmark-route)  
8. [Analytics & Visualization](#-analytics--visualization)  
9. [CLI Commands](#-artisan-commands)  
10. [Integration Use Cases](#-integration-use-cases)  
11. [Compatibility](#-compatibility)  
12. [Compression Visualization](#-example-compression-visualization)  
13. [SEO & AI Integration Keywords](#-seo--ai-integration-keywords)  
14. [License](#-license)  

---

## ✨ Overview  

**TOON for Laravel** — also known as **Token-Optimized Object Notation** — is a **Laravel-native AI optimization library** that converts large JSON or PHP arrays into a **compact, human-readable, and token-efficient format**.  

🧠 Designed for developers integrating **ChatGPT, Gemini, Claude, Mistral, or OpenAI APIs**, TOON helps you:  
✅ Save tokens and reduce costs  
✅ Simplify complex prompt structures  
✅ Improve AI understanding and response quality  
✅ Maintain clarity, reversibility, and human readability  

> 💬 *“Compress your prompts, not your ideas.”*

---

## 🚀 Key Features  

| Feature | Description |
|----------|-------------|
| 🔁 **Bidirectional Conversion** | Convert JSON ⇄ TOON effortlessly |
| 🧩 **Readable & Compact** | YAML-like structure, faster and leaner |
| 💰 **Token-Efficient** | Save up to 70% tokens on AI requests |
| ⚙️ **Laravel Integrated** | Works with Facades, Service Providers, and Artisan |
| 🔒 **Preserves Key Order** | Keeps consistent data ordering |
| 📊 **Built-in Analytics** | Tracks tokens, chars, and byte usage |
| 🌍 **AI & LLM Ready** | Ideal for OpenAI, Gemini, Claude, and Mistral |

---

## 🧪 Real-World Benchmark  

| Metric | JSON | TOON | Reduction |
|---------|------|------|-----------|
| Size (bytes) | 7,718 | 2,538 | **67.12% smaller** |
| Tokens (est.) | 1,930 | 640 | **~66.8% fewer tokens** |

### 📈 Visual Comparison  

```
JSON (7.7 KB)
██████████████████████████████████████████████████████████████████████████

TOON (2.5 KB)
█████████████████
```

💡 **TOON** reduces token load by *60–75%*, improving **AI efficiency** and **prompt performance**.

---

## ⚙️ Installation  

```bash
composer require sbsaga/toon
```

> Laravel auto-discovers the Service Provider and Facade.  

---

## ⚙️ Configuration  

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

🧠 **Visual Graph** (JSON vs TOON Efficiency)

```
| JSON: ██████████████████████████████████████████████████████████ 100%
| TOON: ████████████ 33%
```

---

## 🧰 Artisan Commands  

```bash
php artisan toon:convert storage/test.json
php artisan toon:convert storage/test.toon --decode --pretty
php artisan toon:convert storage/test.json --output=storage/result.toon
```

---

## 🧩 Integration Use Cases  

| Use Case | Benefit |
|-----------|----------|
| 🤖 AI Prompt Engineering | Compress structured context for ChatGPT / LLMs |
| 📉 Token Optimization | Save cost on OpenAI / Gemini / Claude |
| 🧠 Data Preprocessing | Simplify and compress structured inputs |
| 🧾 Logging & Debugging | More readable than JSON |
| 🔍 Developer Tools | Ideal for compact previews and dashboards |

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

🧠 **~67% reduction** with full data integrity.

---

## 🌐 SEO & AI Integration Keywords  

**Keywords:**  
`laravel ai`, `ai for laravel`, `chatgpt laravel`, `laravel chatgpt`, `gemini laravel`, `laravel gemini`, `laravel anthropic`, `laravel mistral`, `laravel openai`, `openai laravel`, `laravel llm`, `llm laravel`, `ai laravel package`, `prompt compression`, `token optimizer`, `laravel json formatter`, `compact notation`, `laravel data compressor`, `token saver`, `laravel ai integration`, `sbsaga toon`, `laravel-toon`, `toon php`, `laravel ai toolkit`.

---

## 💡 Contribution  

Contributions are always welcome!  
- Fork the repo  
- Create a feature branch  
- Commit & push your improvements  
- Submit a Pull Request 🎉  

---

## 📜 License  

Licensed under **MIT** — open for commercial & personal use.

---

<p align="center">
  <b>🧠 “Compress your prompts, not your ideas.” — TOON helps you talk to AI efficiently.</b>
</p>
