<?php
declare(strict_types=1);

/**
 * --------------------------------------------------------------------------
 * TOON Configuration File
 * --------------------------------------------------------------------------
 *
 * This file defines the default settings for the TOON package.
 * TOON (Token-Optimized Object Notation) provides a compact,
 * human-readable data format optimized for AI prompts and LLM contexts.
 *
 * It is especially useful in Laravel applications that interact with
 * AI models (e.g., OpenAI, Claude, Gemini), reducing prompt token count
 * while keeping structure readability intact.
 *
 * Example scenario:
 *  - Suppose Tannu is building a Laravel app that logs AI conversations.
 *  - Instead of storing verbose JSON payloads, she converts them to TOON,
 *    cutting token and storage size by ~70% without losing data fidelity.
 *
 * Author: Sagar S. Bhedodkar
 * License: MIT
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Package Enablement
    |--------------------------------------------------------------------------
    |
    | Determines whether the TOON converter package is globally enabled.
    | When set to `false`, all conversions, commands, and middleware hooks
    | related to TOON will be skipped, preserving default JSON behavior.
    |
    | Example:
    |   Mannu can disable TOON temporarily during testing by setting
    |   TOON_ENABLED=false in her `.env` file.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Escape Style
    |--------------------------------------------------------------------------
    |
    | Defines how special characters (commas, colons, backslashes, etc.)
    | should be escaped when serializing text values into TOON format.
    |
    | Supported:
    |   - 'backslash' → Default and most human-readable style
    |   - Future formats (e.g., 'json') may offer alternate escaping logic.
    |
    | Example:
    |   Surekha’s message "Hello, Sunil!" becomes "Hello\, Sunil!"
    |   when using the 'backslash' escape style.
    |
    */
    'escape_style' => env('TOON_ESCAPE_STYLE', 'backslash'),

    /*
    |--------------------------------------------------------------------------
    | Tabular Rendering Threshold
    |--------------------------------------------------------------------------
    |
    | Determines the minimum number of sequential rows required for an
    | array to be rendered as a tabular block in TOON.
    |
    | Example:
    |   If `min_rows_to_tabular` = 2 and Vikas has:
    |     [['id'=>1,'done'=>false], ['id'=>2,'done'=>true]]
    |   It will render as a TOON table like:
    |
    |     items[2]{done,id}:
    |       false,1
    |       true,2
    |
    | Set a higher value if you prefer smaller arrays to remain inline.
    |
    */
    'min_rows_to_tabular' => env('TOON_MIN_ROWS_TO_TABULAR', 2),

    /*
    |--------------------------------------------------------------------------
    | Preview Item Limit
    |--------------------------------------------------------------------------
    |
    | Controls how many items are displayed when rendering long tabular
    | arrays or datasets in TOON format.
    |
    | This helps keep prompt context concise when serializing large
    | collections — ideal for AI conversations that analyze data from
    | large JSON arrays.
    |
    | Example:
    |   Vitthal’s dataset with 10,000 user rows will only show
    |   the first 200 by default, improving response speed.
    |
    */
    'max_preview_items' => env('TOON_MAX_PREVIEW_ITEMS', 200),

    /*
    |--------------------------------------------------------------------------
    | Scalar Type Coercion
    |--------------------------------------------------------------------------
    |
    | When decoding TOON back to JSON/PHP arrays, this setting decides
    | whether textual scalars such as "true", "false", "null", or "42"
    | should be converted to their native PHP types (boolean, null, int).
    |
    | Example:
    |   Sunil encodes a config file in TOON:
    |       enabled: true
    |   When decoding with coercion enabled → ['enabled' => true]
    |   Without coercion → ['enabled' => 'true']
    |
    | Keeping this true ensures accurate type reconstruction for LLM
    | context or structured data restoration.
    |
    */
    'coerce_scalar_types' => env('TOON_COERCE_SCALARS', true),
];
