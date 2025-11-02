<?php
/**
 * -----------------------------------------------------------------------------
 * TOON Configuration File
 * -----------------------------------------------------------------------------
 *
 * @package     Sbsaga\Toon
 * @author      Sagar Bhedodkar
 * @copyright   (c) 2025 Sagar Bhedodkar
 * @license     MIT License
 * @link        https://github.com/sbsaga/toon
 *
 * @description
 *   This configuration file defines the behavior of the TOON package
 *   within your Laravel application. It allows fine-tuning of rendering,
 *   decoding, and token-optimization behavior to suit various data models
 *   and AI prompt engineering needs.
 *
 * -----------------------------------------------------------------------------
 * ⚙️  OVERVIEW
 * -----------------------------------------------------------------------------
 *  The TOON package converts complex arrays or JSON structures into a
 *  compact, human-readable, and token-efficient representation — ideal for:
 *   • AI/LLM prompt compression
 *   • Structured logging
 *   • Human-readable debugging
 *   • Token optimization for OpenAI/Anthropic contexts
 *
 *  Each of the options below can be overridden using environment variables.
 * -----------------------------------------------------------------------------
 */

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Package Enable Switch
    |--------------------------------------------------------------------------
    |
    | This option controls whether TOON conversion features are active
    | throughout your Laravel application. Disabling this will bypass
    | all conversions and return original JSON output instead.
    |
    | Default: true
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Escape Style
    |--------------------------------------------------------------------------
    |
    | Determines how special characters (commas, colons, newlines, etc.)
    | are escaped during conversion. The default 'backslash' mode provides
    | minimal and readable escaping.
    |
    | Supported: 'backslash' (default)
    | Future options may include: 'json', 'unicode', etc.
    |
    | Env Variable: TOON_ESCAPE_STYLE
    |
    */
    'escape_style' => env('TOON_ESCAPE_STYLE', 'backslash'),

    /*
    |--------------------------------------------------------------------------
    | Minimum Rows for Tabular Rendering
    |--------------------------------------------------------------------------
    |
    | Specifies the minimum number of rows in a sequential array required
    | before TOON formats it as a tabular structure, e.g.:
    |
    |   items[3]{id,name,age}:
    |     1,Sagar,29
    |     2,Sunil,33
    |     3,Tannu,25
    |
    | Lower values make small arrays tabular too, while higher values
    | keep short lists inline for brevity.
    |
    | Env Variable: TOON_MIN_ROWS_TO_TABULAR
    |
    */
    'min_rows_to_tabular' => env('TOON_MIN_ROWS_TO_TABULAR', 2),

    /*
    |--------------------------------------------------------------------------
    | Maximum Preview Items
    |--------------------------------------------------------------------------
    |
    | Defines the number of rows that will be rendered when previewing
    | large tables in TOON format. This prevents excessive output and
    | improves performance for big datasets.
    |
    | Example:
    |   items[5]{id,name,city}:
    |     1,Sagar,Ahmedabad
    |     2,Surekha,Pune
    |     3,Vitthal,Nagpur
    |     4,Sunanda,Surat
    |     5,Vikas,Mumbai
    |
    | Env Variable: TOON_MAX_PREVIEW_ITEMS
    |
    */
    'max_preview_items' => env('TOON_MAX_PREVIEW_ITEMS', 200),

    /*
    |--------------------------------------------------------------------------
    | Scalar Type Coercion
    |--------------------------------------------------------------------------
    |
    | When decoding TOON back into arrays or JSON, this setting determines
    | whether string representations of booleans, numbers, or null values
    | (e.g. "true", "42", "null") should be automatically converted into
    | their native PHP types.
    |
    | Example:
    |   "true"  → true
    |   "42"    → 42
    |   "null"  → null
    |
    | Use Case:
    |   Suppose you have TOON content generated from structured data
    |   describing users like:
    |
    |     users[2]{id,name,active}:
    |       1,Mannu,true
    |       2,Sunil,false
    |
    |   When decoding with coercion enabled, "true"/"false" will be
    |   automatically mapped to proper boolean values.
    |
    | Env Variable: TOON_COERCE_SCALARS
    |
    */
    'coerce_scalar_types' => env('TOON_COERCE_SCALARS', true),

];
