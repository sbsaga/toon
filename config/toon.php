<?php
declare(strict_types=1);

/**
 * Default configuration for the TOON package.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Package Enablement
    |--------------------------------------------------------------------------
    |
    | Application-level feature flag for TOON integrations.
    | Host applications may use this value to gate TOON-specific behavior.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Escape Style
    |--------------------------------------------------------------------------
    |
    | Escaping strategy used when serializing inline string values.
    | The current implementation supports the backslash style used by the
    | encoder and decoder.
    |
    */
    'escape_style' => env('TOON_ESCAPE_STYLE', 'backslash'),

    /*
    |--------------------------------------------------------------------------
    | Field Delimiter
    |--------------------------------------------------------------------------
    |
    | Delimiter used for tabular output and primitive array rendering.
    | Supported values are "comma", "pipe", and "tab", or the raw delimiter
    | character itself.
    |
    */
    'delimiter' => env('TOON_DELIMITER', 'comma'),

    /*
    |--------------------------------------------------------------------------
    | Tabular Rendering Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum number of rows required before a uniform list is emitted as a
    | compact TOON table instead of a line-by-line sequence.
    |
    */
    'min_rows_to_tabular' => env('TOON_MIN_ROWS_TO_TABULAR', 2),

    /*
    |--------------------------------------------------------------------------
    | Preview Item Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of rows emitted when a list is rendered in tabular form.
    | This keeps large payloads compact when TOON is used for previews or
    | prompt construction.
    |
    */
    'max_preview_items' => env('TOON_MAX_PREVIEW_ITEMS', 200),

    /*
    |--------------------------------------------------------------------------
    | Scalar Type Coercion
    |--------------------------------------------------------------------------
    |
    | Convert textual scalars such as "true", "false", "null", and numeric
    | values to their native PHP types during decoding.
    |
    */
    'coerce_scalar_types' => env('TOON_COERCE_SCALARS', true),

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, decoding validates row widths and expected row counts for
    | tabular TOON payloads and throws an exception on malformed input.
    |
    */
    'strict_mode' => env('TOON_STRICT_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Compatibility Mode
    |--------------------------------------------------------------------------
    |
    | "legacy" preserves the original package behavior by default so existing
    | applications can upgrade safely.
    | "modern" enables safer round trips and more predictable nested-data output
    | for new projects or opt-in migrations.
    |
    */
    'compatibility_mode' => env('TOON_COMPATIBILITY_MODE', 'legacy'),
];
