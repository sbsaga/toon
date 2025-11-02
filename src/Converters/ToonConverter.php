<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Converters;

use Sbsaga\Toon\Exceptions\ToonException;

/**
 * Class ToonConverter
 *
 * Converts arrays, objects, and JSON strings into TOON format.
 * Provides clean, compact, and human-readable data serialization.
 *
 * Design goals:
 *  - Preserve key order (no sorting)
 *  - Keep TOON human-friendly and consistent
 *  - Support both scalar and nested structures
 *  - Safely escape special characters
 */
class ToonConverter
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'min_rows_to_tabular' => 2,
            'max_preview_items' => 100,
            'escape_style' => 'backslash',
        ], $config);
    }

    /**
     * Convert arbitrary input (JSON string, array, object, scalar) to TOON string.
     */
    public function toToon(mixed $input): string
    {
        // If it's a JSON string, try decoding it first
        if (is_string($input) && $this->looksLikeJson($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->valueToToon($decoded);
            }
        }

        // Convert object to associative array
        if (is_object($input)) {
            $input = json_decode(json_encode($input), true);
        }

        if (is_array($input) || $input instanceof \Traversable) {
            return $this->valueToToon((array) $input);
        }

        return $this->textToToon((string) $input);
    }

    /**
     * Recursive conversion for arrays and scalar values.
     */
    protected function valueToToon(mixed $value, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);

        if (is_array($value)) {
            // Sequential (list) array
            if ($this->isSequentialArray($value)) {
                // If array contains uniform objects, render as compact table
                if ($this->isArrayOfUniformObjects($value)) {
                    return $this->arrayOfObjectsToToon($value, $depth);
                }

                // Otherwise, render each item line by line
                $lines = [];
                foreach ($value as $item) {
                    if ($this->isScalar($item)) {
                        $lines[] = $indent . $this->inlineScalar($item);
                    } else {
                        $lines[] = $indent . $this->valueToToon($item, $depth + 1);
                    }
                }
                return implode("\n", $lines);
            }

            // Associative object — preserve original key order
            $lines = [];
            foreach ($value as $key => $val) {
                $safeKey = $this->safeKey((string) $key);
                if ($this->isScalar($val)) {
                    $lines[] = $indent . "{$safeKey}: " . $this->inlineScalar($val);
                } else {
                    $lines[] = $indent . "{$safeKey}:";
                    $lines[] = $this->valueToToon($val, $depth + 1);
                }
            }
            return implode("\n", $lines);
        }

        // Fallback: scalar value
        return $indent . $this->inlineScalar($value);
    }

    /**
     * Render a uniform array of associative objects into compact TOON table form.
     *
     * Format example:
     * items[3]{id,name,active}:
     *   1,John,true
     *   2,Jane,false
     */
    protected function arrayOfObjectsToToon(array $arr, int $depth = 0): string
    {
        if (empty($arr)) {
            return str_repeat('  ', $depth) . 'items[0]{}:';
        }

        $first = (array) $arr[0];
        $fields = array_keys($first); // preserve field order as provided
        $indent = str_repeat('  ', $depth);

        $header = $indent . 'items[' . count($arr) . ']{' . implode(',', $fields) . '}:';
        $rows = [];
        $max = min(count($arr), (int) $this->config['max_preview_items']);

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
     * Escape and format scalar values safely for TOON syntax.
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

        $s = trim(preg_replace('/\s+/', ' ', (string) $v));

        if ($this->config['escape_style'] === 'backslash') {
            $s = str_replace('\\', '\\\\', $s);
            $s = str_replace(',', '\\,', $s);
            $s = str_replace(':', '\\:', $s);
            $s = str_replace("\n", '\\n', $s);
            return $s;
        }

        return str_replace("\n", '\\n', $s);
    }

    /**
     * Convert a plain text string to a TOON-safe line.
     */
    protected function textToToon(string $text): string
    {
        return $this->inlineScalar($text);
    }

    /**
     * Sanitize a key name for TOON output.
     */
    protected function safeKey(string $k): string
    {
        $key = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $k);
        return strtolower($key);
    }

    /**
     * Utility helpers
     */
    protected function isScalar(mixed $v): bool
    {
        return is_null($v) || is_scalar($v);
    }

    protected function looksLikeJson(string $s): bool
    {
        $s = trim($s);
        return $s !== '' && (str_starts_with($s, '{') || str_starts_with($s, '['));
    }

    protected function isSequentialArray(array $arr): bool
    {
        return array_values($arr) === $arr;
    }

    /**
     * Detect whether an array is a uniform list of objects with identical keys.
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

            $keys = array_keys($item); // preserve key order (no sorting)
            if ($firstKeys === null) {
                $firstKeys = $keys;
            } elseif ($keys !== $firstKeys) {
                return false;
            }
        }

        return true;
    }
}
