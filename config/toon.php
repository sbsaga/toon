<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TOON configuration
    |--------------------------------------------------------------------------
    |
    | enabled: enable/disable conversions if you want to use decode only etc.
    | escape_style: how to escape special characters in inline values (toon spec uses backslash).
    | max_preview_items: limit number of rows shown for very large arrays.
    | min_rows_to_tabular: minimum rows to detect table-style encoding.
    |
    */

    'enabled' => true,
    'escape_style' => 'backslash', // "backslash" (\,\: \\ \n) - kept for clarity
    'min_rows_to_tabular' => 2,
    'max_preview_items' => 200,
];
