<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Converters;

use Sbsaga\Toon\Exceptions\ToonException;

/**
 * Class ToonConverter
 *
 * Converts arrays, objects, and JSON strings into TOON format.
 * TOON is a lightweight, human-readable data representation format
 * designed for structured serialization — cleaner than JSON, and
 * easier to scan and edit manually.
 *
 * Core responsibilities:
 * - Convert PHP arrays, objects, and JSON strings into TOON syntax.
 * - Handle both associative and sequential arrays.
 * - Produce compact tabular layouts when applicable.
 * - Ensure safe escaping of special characters.
 *
 * Example Usage:
 * ```php
 * use Sbsaga\Toon\Converters\ToonConverter;
 *
 * $converter = new ToonConverter();
 * $data = [
 *     ['id' => 1, 'name' => 'Sagar', 'active' => true],
 *     ['id' => 2, 'name' => 'Sunil', 'active' => false],
 * ];
 * echo $converter->toToon($data);
 * ```
 *
 * Output:
 * ```
 * items[2]{id,name,active}:
 *   1,Sagar,true
 *   2,Sunil,false
 * ```
 */
class ToonConverter
{
    /**
     * The configuration options used by the converter.
     * 
     * Supported keys:
     * - min_rows_to_tabular: minimum number of rows required for tabular rendering.
     * - max_preview_items: maximum number of rows to include in table output.
     * - escape_style: escaping mode ('backslash' supported).
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Create a new ToonConverter instance.
     *
     * @param array $config Optional custom configuration.
     */
    public function __construct(array $config = [])
    {
        // Merge user-supplied configuration with defaults.
        $this->config = array_merge([
            'min_rows_to_tabular' => 2,
            'max_preview_items'   => 100,
            'escape_style'        => 'backslash',
        ], $config);
    }

    /**
     * Convert any PHP value into a TOON-formatted string.
     *
     * @param mixed $input Can be an array, object, JSON string, or scalar.
     * @return string The resulting TOON representation.
     */
    public function toToon(mixed $input): string
    {
        // If the input looks like JSON, attempt to decode it first.
        if (is_string($input) && $this->looksLikeJson($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->valueToToon($decoded);
            }
        }

        // Convert objects to associative arrays to ensure uniform processing.
        if (is_object($input)) {
            $input = json_decode(json_encode($input), true);
        }

        // Arrays (or Traversable) are processed recursively.
        if (is_array($input) || $input instanceof \Traversable) {
            return $this->valueToToon((array) $input);
        }

        // Fallback for plain text or scalar inputs.
        return $this->textToToon((string) $input);
    }

    /**
     * Recursively convert arrays and scalar values into TOON format.
     *
     * @param mixed $value The value to be converted.
     * @param int $depth Used internally to manage indentation for nested data.
     * @return string
     */
    protected function valueToToon(mixed $value, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth); // Two spaces per depth level.

        if (is_array($value)) {
            // Handle sequential (numeric) arrays.
            if ($this->isSequentialArray($value)) {
                // If array items are objects with uniform keys, render a table.
                if ($this->isArrayOfUniformObjects($value)) {
                    return $this->arrayOfObjectsToToon($value, $depth);
                }

                // Otherwise, render each array element on a new line.
                $lines = [];
                foreach ($value as $item) {
                    if ($this->isScalar($item)) {
                        $lines[] = $indent . $this->inlineScalar($item);
                    } else {
                        // For nested structures, recurse with increased depth.
                        $lines[] = $indent . $this->valueToToon($item, $depth + 1);
                    }
                }
                return implode("\n", $lines);
            }

            // Handle associative arrays (objects).
            $lines = [];
            foreach ($value as $key => $val) {
                $safeKey = $this->safeKey((string) $key);

                // Inline simple key-value pairs.
                if ($this->isScalar($val)) {
                    $lines[] = $indent . "{$safeKey}: " . $this->inlineScalar($val);
                } else {
                    // For nested arrays/objects, start a new block.
                    $lines[] = $indent . "{$safeKey}:";
                    $lines[] = $this->valueToToon($val, $depth + 1);
                }
            }
            return implode("\n", $lines);
        }

        // Fallback for single scalar value.
        return $indent . $this->inlineScalar($value);
    }

    /**
     * Convert an array of uniform associative objects into a compact table layout.
     *
     * Example:
     * ```
     * items[3]{id,name,age}:
     *   1,Sagar,30
     *   2,Tannu,25
     *   3,Sunil,28
     * ```
     *
     * @param array $arr List of associative arrays.
     * @param int $depth Indentation depth for nested data.
     * @return string
     */
    protected function arrayOfObjectsToToon(array $arr, int $depth = 0): string
    {
        if (empty($arr)) {
            return str_repeat('  ', $depth) . 'items[0]{}:'; // Handle empty arrays gracefully.
        }

        $first = (array) $arr[0];
        $fields = array_keys($first); // Preserve the order of keys as provided.
        $indent = str_repeat('  ', $depth);

        $header = $indent . 'items[' . count($arr) . ']{' . implode(',', $fields) . '}:';
        $rows = [];
        $max = min(count($arr), (int) $this->config['max_preview_items']);

        // Loop through each object and serialize its field values.
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
     * Safely escape and format scalar values for TOON syntax.
     *
     * Ensures that commas, colons, backslashes, and newlines are
     * properly escaped so that the resulting TOON output remains parseable.
     *
     * @param mixed $v Scalar value to process.
     * @return string Escaped string representation.
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

        // Normalize whitespace to single spaces.
        $s = trim(preg_replace('/\s+/', ' ', (string) $v));

        // Backslash-based escaping (default mode).
        if ($this->config['escape_style'] === 'backslash') {
            $s = str_replace('\\', '\\\\', $s);
            $s = str_replace(',', '\\,', $s);
            $s = str_replace(':', '\\:', $s);
            $s = str_replace("\n", '\\n', $s);
            return $s;
        }

        // Basic newline escape fallback.
        return str_replace("\n", '\\n', $s);
    }

    /**
     * Convert plain text into TOON-safe inline representation.
     *
     * @param string $text Raw input text.
     * @return string Escaped string.
     */
    protected function textToToon(string $text): string
    {
        return $this->inlineScalar($text);
    }

    /**
     * Sanitize and normalize a key name for TOON output.
     *
     * Only allows alphanumeric characters, underscores, dashes, and periods.
     *
     * @param string $k The raw key.
     * @return string Clean, lowercased key name.
     */
    protected function safeKey(string $k): string
    {
        $key = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $k);
        return strtolower($key);
    }

    /* --------------------------------------------------------------------
     |  Utility Methods
     |-------------------------------------------------------------------- */

    /**
     * Determine if a value is scalar or null.
     *
     * @param mixed $v
     * @return bool
     */
    protected function isScalar(mixed $v): bool
    {
        return is_null($v) || is_scalar($v);
    }

    /**
     * Determine if a string likely represents a JSON structure.
     *
     * @param string $s Input string.
     * @return bool
     */
    protected function looksLikeJson(string $s): bool
    {
        $s = trim($s);
        return $s !== '' && (str_starts_with($s, '{') || str_starts_with($s, '['));
    }

    /**
     * Check whether the given array is sequential (numeric, ordered list).
     *
     * @param array $arr
     * @return bool
     */
    protected function isSequentialArray(array $arr): bool
    {
        return array_values($arr) === $arr;
    }

    /**
     * Check if the array is a uniform list of associative arrays
     * where all items have identical keys (used for table rendering).
     *
     * @param array $arr
     * @return bool
     */
    protected function isArrayOfUniformObjects(array $arr): bool
    {
        $min = (int) $this->config['min_rows_to_tabular'];
        if (count($arr) < $min) {
            return false; // Not enough rows for table formatting.
        }

        $firstKeys = null;
        foreach ($arr as $item) {
            if (!is_array($item)) {
                return false; // Must all be arrays.
            }

            $keys = array_keys($item); // Preserve key order as provided.
            if ($firstKeys === null) {
                $firstKeys = $keys;
            } elseif ($keys !== $firstKeys) {
                // If any item has mismatched keys, table rendering not possible.
                return false;
            }
        }

        return true;
    }
}
