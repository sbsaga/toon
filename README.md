<h1 align="center">
  <img src="https://laravel.com/img/logomark.min.svg" height="60" alt="Laravel Logo">
  <br>
  🧠 Laravel TOON  
</h1>

<p align="center">
  <strong>Compact, Token-Optimized Data Format for AI Prompts & LLM Contexts</strong>  
</p>

<p align="center">
  <a href="https://packagist.org/packages/sbsaga/laravel-toon"><img src="https://img.shields.io/packagist/v/sbsaga/laravel-toon.svg?style=for-the-badge&color=ff2d20" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/sbsaga/laravel-toon"><img src="https://img.shields.io/packagist/dt/sbsaga/laravel-toon.svg?style=for-the-badge&color=orange" alt="Total Downloads"></a>
  <a href="https://github.com/sbsaga/laravel-toon/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg?style=for-the-badge" alt="License"></a>
  <img src="https://img.shields.io/badge/Laravel-11.x-red?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB3?style=for-the-badge&logo=php" alt="PHP">
</p>

---

## 🚀 Overview

**Laravel TOON** is a lightweight utility that converts complex PHP arrays, JSON, or text into a **compact, human-readable format** called **TOON** (Token Optimized Object Notation).  
Perfect for **AI prompt optimization**, **LLM data preprocessing**, and **reducing token usage** in chat-based models.

> 🧩 Seamlessly converts between JSON ⇆ TOON and even estimates token counts.

---

## ✨ Features

- 🔁 **Convert JSON / Arrays / Text → TOON**
- 🔄 **Decode TOON → PHP / JSON**
- ⚙️ **Built-in CLI command:** `php artisan toon:convert`
- 🧮 **Token estimation for AI efficiency**
- ⚡ **Zero configuration needed**
- 💡 **Ideal for AI agents & prompt compression**

---

## 🧱 Installation

```bash
composer require sbsaga/laravel-toon
```

Laravel will auto-discover the service provider and facade.

---

## ⚙️ Usage

### ➤ Convert JSON or Array to TOON

```php
use Sbsaga\Toon\Facades\Toon;

$data = [
    'user' => 'Sagar',
    'tasks' => [
        ['id' => 1, 'done' => false],
        ['id' => 2, 'done' => true],
    ],
    'meta' => [
        'version' => '1.0.8',
        'enabled' => true,
    ],
];

$toon = Toon::convert($data);

echo $toon;
```

**Output (TOON format):**

```yaml
meta:
  enabled: true
  version: 1.0.8
tasks:
  items[2]{done,id}:
    false,1
    true,2
user: Sagar
```

---

### ➤ Decode TOON to JSON / PHP Array

```php
$decoded = Toon::decode($toon);

print_r($decoded);
```

---

### 🧮 Estimate Tokens

```php
$tokens = Toon::estimateTokens($toon);

print_r($tokens);
```

Output example:

```json
{
  "words": 20,
  "chars": 182,
  "tokens_estimate": 19
}
```

---

## 🖥️ CLI Command

You can also use Laravel’s artisan command:

```bash
php artisan toon:convert input.json
php artisan toon:convert --decode input.toon
php artisan toon:convert input.json --output=output.toon
```

---

## 🧩 Example

**Input JSON:**

```json
{
  "user": "Sagar",
  "tasks": [
    {"id": 1, "done": false},
    {"id": 2, "done": true}
  ],
  "meta": {
    "version": "1.0.8",
    "enabled": true
  }
}
```

**Converted TOON:**

```yaml
meta:
  enabled: true
  version: 1.0.8
tasks:
  items[2]{done,id}:
    false,1
    true,2
user: Sagar
```

---

## 🧰 Configuration (Optional)

```php
// config/toon.php

return [
    'enabled' => true,
    'escape_style' => 'backslash',
    'min_rows_to_tabular' => 2,
    'max_preview_items' => 200,
];
```

> You can publish the config file via:  
> `php artisan vendor:publish --tag=config`

---

## 📜 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

<p align="center">
  <sub>Built with ❤️ by <a href="https://github.com/sbsaga">Sagar</a></sub>
</p>
