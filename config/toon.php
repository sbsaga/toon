<?php
declare(strict_types=1);

return [
    /*
     * Enable the package.
     */
    'enabled' => true,

    /*
     * Escape style.
     * Supported: 'backslash' (default), future options (e.g., 'json')
     */
    'escape_style' => env('TOON_ESCAPE_STYLE', 'backslash'),

    /*
     * Minimum rows in a sequential array to be rendered as a tabular 'items[...]' block.
     */
    'min_rows_to_tabular' => env('TOON_MIN_ROWS_TO_TABULAR', 2),

    /*
     * Max number of rows to show when rendering a table in TOON format.
     */
    'max_preview_items' => env('TOON_MAX_PREVIEW_ITEMS', 200),

    /*
     * Whether to coerce scalar textual values "true"/"false"/"null"/numbers back to their native types when decoding.
     */
    'coerce_scalar_types' => env('TOON_COERCE_SCALARS', true),
];
