<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Converters;

use Sbsaga\Toon\Exceptions\ToonException;

/**
 * --------------------------------------------------------------------------
 * Class: ToonConverter
 * --------------------------------------------------------------------------
 * 
 * The ToonConverter class provides methods to transform PHP data structures
 * (arrays, objects, scalars) or JSON strings into the lightweight, human-readable
 * **TOON format**.
 * 
 * TOON is designed for developers who need a compact, readable serialization
 * format that is easier to scan, compare, or store in plain text compared to JSON.
 * 
 * --------------------------------------------------------------------------
 * 🧠 Design Goals:
 * --------------------------------------------------------------------------
 *  - **Preserve key order:** keeps associative arrays in original sequence.
 *  - **Human readability:** creates consistent indentation and escaping.
 *  - **Compact tabular display:** auto-renders uniform object arrays into tables.
 *  - **Safe escaping:** prevents confusion when values contain commas, colons, etc.
 *  - **Deterministic output:** identical input always produces identical TOON text.
 * 
 * Example Conversion:
 * -------------------
 * Input (PHP Array):
 * [
 *   ['id' => 1, 'name' => 'Sagar', 'active' => true],
 *   ['id' => 2, 'name' => 'Sunil', 'active' => false],
 * ]
 * 
 * Output (TOON):
 * items[2]{id,name,active}:
 *   1,Sagar,true
 *   2,Sunil,false
 * 
 * --------------------------------------------------------------------------
 * Author:  Sagar Bhedodkar
 * License: MIT
 * --------------------------------------------------------------------------
 */
class ToonConverter
{
    /**
     * Holds the configuration values controlling TOON formatting behavior.
     * 
     * Supported config keys:
     * - `min_rows_to_tabular`: Minimum rows before using table-style rendering.
     * - `max_preview_items`:   Maximum number of items shown when rendering a table.
     * - `escape_style`:        Escaping mode (e.g., "backslash").
     */
    protected array $config;

    /**
     * Create a new ToonConverter instance with optional overrides.
     *
     * @param array $config  Custom configuration values.
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'min_rows_to_tabular' => 2,
            'max_preview_items' => 100,
            'escape_style' => 'backslash',
        ], $config);
    }

    /**
     * ----------------------------------------------------------------------
     * Convert arbitrary input into TOON format.
     * ----------------------------------------------------------------------
     *
     * Accepts JSON strings, PHP arrays, objects, or simple scalar values.
     * 
     * Internally:
     *  1. Attempts to decode JSON strings automatically.
     *  2. Converts objects to associative arrays.
     *  3. Recursively serializes nested structures to TOON text.
     * 
     * @param mixed $input  The input value (array, object, string, scalar).
     * @return string       The TOON representation.
     */
    public function toToon(mixed $input): string
    {
        // Attempt to decode JSON strings automatically for convenience
        if (is_string($input) && $this->looksLikeJson($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->valueToToon($decoded);
            }
        }

        // Convert objects (stdClass, DTOs, etc.) to associative arrays
        if (is_object($input)) {
            $input = json_decode(json_encode($input), true);
        }

        // Process arrays or traversable items recursively
        if (is_array($input) || $input instanceof \Traversable) {
            return $this->valueToToon((array) $input);
        }

        // Scalar values (string, int, bool, float, etc.)
        return $this->textToToon((string) $input);
    }

    /**
     * ----------------------------------------------------------------------
     * Recursively convert arrays, lists, or scalar values into TOON.
     * ----------------------------------------------------------------------
     *
     * This method forms the backbone of TOON rendering.
     * It handles:
     *   - Sequential arrays (lists)
     *   - Associative arrays (objects)
     *   - Scalar leaf values
     *
     * @param mixed $value  The data to serialize.
     * @param int   $depth  Current nesting depth (controls indentation).
     * @return string       Serialized TOON text.
     */
    protected function valueToToon(mixed $value, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);

        if (is_array($value)) {
            // Handle SEQUENTIAL arrays (e.g., numeric lists)
            if ($this->isSequentialArray($value)) {
                // If array consists of uniform associative objects, render as TOON table
                if ($this->isArrayOfUniformObjects($value)) {
                    return $this->arrayOfObjectsToToon($value, $depth);
                }

                // Otherwise, render each list item on its own line or block
                $lines = [];
                foreach ($value as $item) {
                    if ($this->isScalar($item)) {
                        $lines[] = $indent . $this->inlineScalar($item);
                    } else {
                        // Non-scalar: recursively serialize with increased depth
                        $lines[] = $indent . $this->valueToToon($item, $depth + 1);
                    }
                }
                return implode("\n", $lines);
            }

            // Handle ASSOCIATIVE arrays (object-like structures)
            // TOON preserves original order of keys (no sorting)
            $lines = [];
            foreach ($value as $key => $val) {
                $safeKey = $this->safeKey((string) $key);
                if ($this->isScalar($val)) {
                    // Key-value pairs on single line
                    $lines[] = $indent . "{$safeKey}: " . $this->inlineScalar($val);
                } else {
                    // Nested object values rendered with increased indentation
                    $lines[] = $indent . "{$safeKey}:";
                    $lines[] = $this->valueToToon($val, $depth + 1);
                }
            }
            return implode("\n", $lines);
        }

        // Fallback: direct scalar rendering
        return $indent . $this->inlineScalar($value);
    }

    /**
     * ----------------------------------------------------------------------
     * Convert an array of uniform associative objects into TOON table format.
     * ----------------------------------------------------------------------
     *
     * TOON Table Example:
     * -------------------
     * items[3]{id,name,active}:
     *   1,Sagar,true
     *   2,Sunil,false
     *   3,Vitthal,true
     *
     * @param array $arr   Array of uniform associative arrays (same keys).
     * @param int   $depth Current indentation level.
     * @return string
     */
    protected function arrayOfObjectsToToon(array $arr, int $depth = 0): string
    {
        if (empty($arr)) {
            return str_repeat('  ', $depth) . 'items[0]{}:';
        }

        // Determine table headers (preserve original key order)
        $first = (array) $arr[0];
        $fields = array_keys($first);
        $indent = str_repeat('  ', $depth);

        // Build table header with row count and field list
        $header = $indent . 'items[' . count($arr) . ']{' . implode(',', $fields) . '}:';

        // Limit number of rows based on config
        $rows = [];
        $max = min(count($arr), (int) $this->config['max_preview_items']);

        // Render each data row as a comma-separated line
        for ($i = 0; $i < $max; $i++) {
            $row = [];
            foreach ($fields as $f) {
                $row[] = $this->inlineScalar($arr[$i][$f] ?? null);
            }
            $rows[] = $indent . '  ' . implode(',', $row);
        }

        return $header . "\n" . implode("\n", $rows);
    }

    /**
     * ----------------------------------------------------------------------
     * Format a scalar value (string, number, bool, etc.) for TOON syntax.
     * ----------------------------------------------------------------------
     *
     * Applies escaping rules depending on configured `escape_style`.
     * - Escapes commas, colons, and backslashes.
     * - Converts newlines to "\n".
     *
     * @param mixed $v
     * @return string
     */
    protected function inlineScalar(mixed $v): string
    {
        if ($v === null) {
            return '';
        }

        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }

        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }

        // Normalize whitespace inside strings
        $s = trim(preg_replace('/\s+/', ' ', (string) $v));

        // Apply configured escaping style
        if ($this->config['escape_style'] === 'backslash') {
            $s = str_replace('\\', '\\\\', $s);
            $s = str_replace(',', '\\,', $s);
            $s = str_replace(':', '\\:', $s);
            $s = str_replace("\n", '\\n', $s);
            return $s;
        }

        // Default minimal escaping
        return str_replace("\n", '\\n', $s);
    }

    /**
     * Convert plain text directly to a safe TOON representation.
     *
     * @param string $text
     * @return string
     */
    protected function textToToon(string $text): string
    {
        return $this->inlineScalar($text);
    }

    /**
     * Sanitize object or array keys for valid TOON identifiers.
     *
     * Removes invalid symbols and enforces lowercase output.
     *
     * @param string $k
     * @return string
     */
    protected function safeKey(string $k): string
    {
        $key = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $k);
        return strtolower($key);
    }

    // ----------------------------------------------------------------------
    // Utility Helpers
    // ----------------------------------------------------------------------

    /**
     * Check if a value is scalar (string, int, float, bool, null).
     *
     * @param mixed $v
     * @return bool
     */
    protected function isScalar(mixed $v): bool
    {
        return is_null($v) || is_scalar($v);
    }

    /**
     * Determine if a string looks like JSON (used for auto-detection).
     *
     * @param string $s
     * @return bool
     */
    protected function looksLikeJson(string $s): bool
    {
        $s = trim($s);
        return $s !== '' && (str_starts_with($s, '{') || str_starts_with($s, '['));
    }

    /**
     * Determine if an array is a numeric, sequential list.
     *
     * @param array $arr
     * @return bool
     */
    protected function isSequentialArray(array $arr): bool
    {
        return array_values($arr) === $arr;
    }

    /**
     * Detect whether an array contains uniform associative objects
     * (all items having identical keys in the same order).
     *
     * @param array $arr
     * @return bool
     */
    protected function isArrayOfUniformObjects(array $arr): bool
    {
        $min = (int) $this->config['min_rows_to_tabular'];
        if (count($arr) < $min) {
            return false;
        }

        $firstKeys = null;
        foreach ($arr as $item) {
            if (!is_array($item)) {
                return false;
            }

            $keys = array_keys($item); // Preserve key order (no sorting)
            if ($firstKeys === null) {
                $firstKeys = $keys;
            } elseif ($keys !== $firstKeys) {
                return false;
            }
        }

        return true;
    }
}
