<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Converters;

/**
 * Serializes PHP data structures into TOON.
 *
 * ```php
 * $converter = new ToonConverter(['min_rows_to_tabular' => 1]);
 *
 * echo $converter->toToon([
 *     ['id' => 1, 'name' => 'Alice'],
 *     ['id' => 2, 'name' => 'Bob'],
 * ]);
 * ```
 */
class ToonConverter
{
    /**
     * Runtime encoder configuration.
     *
     * @var array{
     *     min_rows_to_tabular:int,
     *     max_preview_items:int,
     *     escape_style:string,
     *     delimiter:string,
     *     compatibility_mode:string
     * }
     */
    protected array $config;

    /**
     * Create a new converter instance.
     *
     * @param array $config Optional configuration overrides.
     */
    public function __construct(array $config = [])
    {
        // Merge caller-provided overrides with package defaults.
        $this->config = array_merge([
            'min_rows_to_tabular' => 2,
            'max_preview_items' => 100,
            'escape_style' => 'backslash',
            'delimiter' => 'comma',
            'compatibility_mode' => 'legacy',
        ], $config);
    }

    /**
     * Encode JSON strings, arrays, objects, or scalar values into TOON.
     *
     * @param mixed $input Data to encode.
     * @return string Serialized TOON payload.
     */
    public function toToon(mixed $input): string
    {
        // Decode JSON input first so callers can pass raw payloads directly.
        if (is_string($input) && $this->looksLikeJson($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->valueToToon($decoded);
            }
        }

        // Normalize objects to associative arrays before recursive rendering.
        if (is_object($input)) {
            $input = json_decode(json_encode($input), true);
        }

        // Traversables and arrays share the same recursive rendering path.
        if (is_array($input) || $input instanceof \Traversable) {
            return $this->valueToToon((array) $input);
        }

        // Scalar values are emitted as a single inline TOON fragment.
        return $this->textToToon((string) $input);
    }

    /**
     * Render a value at the requested indentation depth.
     *
     * @param mixed $value Value to render.
     * @param int $depth Current indentation depth.
     * @return string Serialized TOON fragment.
     */
    protected function valueToToon(mixed $value, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);

        if (is_array($value)) {
            // Sequential arrays are rendered either as tables or as one item per line.
            if ($this->isSequentialArray($value)) {
                if ($value === []) {
                    return '';
                }

                // Render uniform record lists as compact table blocks when every cell is safe inline.
                if ($this->isArrayOfUniformObjects($value)) {
                    return $this->arrayOfObjectsToToon($value, $depth);
                }

                $lines = [];
                foreach ($value as $item) {
                    if ($this->isScalar($item)) {
                        // Scalar items remain inline at the current indentation level.
                        $lines[] = $indent . $this->inlineScalar($item);
                        continue;
                    }

                    if ($this->isLegacyMode()) {
                        // Preserve the original expanded list style for legacy consumers.
                        $nested = $this->valueToToon($item, $depth + 1);
                        if ($nested !== '') {
                            $lines[] = $nested;
                        }
                    } else {
                        // Modern mode marks complex list items explicitly for safer decoding.
                        $lines[] = $indent . '-';

                        $nested = $this->valueToToon($item, $depth + 1);
                        if ($nested !== '') {
                            $lines[] = $nested;
                        }
                    }
                }

                return implode("\n", $lines);
            }

            // Associative arrays preserve input order and render as key/value pairs.
            $lines = [];
            foreach ($value as $key => $val) {
                $safeKey = $this->safeKey((string) $key);

                if ($this->isScalar($val)) {
                    $lines[] = $indent . "{$safeKey}: " . $this->inlineScalar($val);
                    continue;
                }

                // Nested structures are emitted on subsequent indented lines.
                $lines[] = $indent . "{$safeKey}:";

                $nested = $this->valueToToon($val, $depth + 1);
                if ($nested !== '') {
                    $lines[] = $nested;
                }
            }

            return implode("\n", $lines);
        }

        // Non-array values are rendered directly at the current indentation level.
        return $indent . $this->inlineScalar($value);
    }

    /**
     * Render a uniform list of associative arrays as a TOON table.
     *
     * ```text
     * items[2]{id,name}:
     *   1,Alice
     *   2,Bob
     * ```
     *
     * @param array $arr Uniform record list.
     * @param int $depth Indentation level for nested rendering.
     * @return string Serialized TOON table.
     */
    protected function arrayOfObjectsToToon(array $arr, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);

        // Preserve a valid table header even for empty collections.
        if (empty($arr)) {
            return $indent . 'items[0]{}:';
        }

        // The first row defines both field order and the table schema.
        $first = (array) $arr[0];
        $originalFields = array_keys($first);
        $fields = $this->isLegacyMode()
            ? $originalFields
            : array_map(fn (string $field): string => $this->safeKey($field), $originalFields);
        $separator = $this->isLegacyMode() ? ',' : $this->getDelimiter();

        $header = $indent . 'items[' . count($arr) . ']{' . implode($separator, $fields) . '}:';
        $rows = [];

        // Modern mode always serializes the full collection to avoid silent truncation.
        $max = count($arr);
        if ($this->isLegacyMode()) {
            $max = min($max, (int) $this->config['max_preview_items']);
        }

        for ($i = 0; $i < $max; $i++) {
            $row = [];
            foreach ($originalFields as $field) {
                // Missing keys are rendered as null-equivalent empty cells.
                $row[] = $this->inlineScalar($arr[$i][$field] ?? null);
            }
            $rows[] = $indent . '  ' . implode($separator, $row);
        }

        return $header . ($rows === [] ? '' : "\n" . implode("\n", $rows));
    }

    /**
     * Convert a scalar-like value into an inline TOON representation.
     *
     * @param mixed $value Value to render.
     * @return string Escaped inline representation.
     */
    protected function inlineScalar(mixed $value): string
    {
        // Null is represented as an empty value so keys can still be emitted.
        if ($value === null) {
            return '';
        }

        // Booleans and numbers are written without additional escaping.
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        // Legacy mode preserves the package's original inline array flattening behavior.
        if (is_array($value)) {
            if ($this->isLegacyMode()) {
                $parts = [];
                foreach ($value as $key => $item) {
                    $parts[] = (string) $key . ':' . $this->inlineScalar($item);
                }

                return implode(',', $parts);
            }

            return $this->escapeInlineString((string) json_encode(
                $value,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ));
        }

        return $this->escapeInlineString((string) $value);
    }

    /**
     * Convert plain text to a TOON-safe inline value.
     *
     * @param string $text Text to encode.
     * @return string Escaped TOON-safe text.
     */
    protected function textToToon(string $text): string
    {
        return $this->inlineScalar($text);
    }

    /**
     * Normalize a key name for TOON output.
     *
     * @param string $key Raw key name.
     * @return string Normalized key.
     */
    protected function safeKey(string $key): string
    {
        if ($this->isLegacyMode()) {
            // Restrict keys to the original TOON-safe character set used by the legacy decoder regex.
            $normalized = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $key) ?? '';
            return strtolower($normalized);
        }

        $normalized = preg_replace('/\s+/', '_', trim($key)) ?? '';
        $normalized = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $normalized) ?? '';

        return $normalized !== '' ? $normalized : 'field';
    }

    /** Determine whether the value can be emitted inline without recursion. */
    protected function isScalar(mixed $value): bool
    {
        return $value === null || is_scalar($value);
    }

    /** Detect whether the input appears to be a JSON document. */
    protected function looksLikeJson(string $value): bool
    {
        $value = trim($value);
        return $value !== '' && (str_starts_with($value, '{') || str_starts_with($value, '['));
    }

    /** Determine whether the array is a zero-based list. */
    protected function isSequentialArray(array $array): bool
    {
        return array_values($array) === $array;
    }

    /**
     * Determine whether the array can be emitted as a uniform TOON table.
     *
     * All rows must be associative arrays with the same keys in the same order.
     * Modern mode also requires every cell to be safely representable inline.
     *
     * @param array $array Candidate list.
     * @return bool True when the list is suitable for tabular output.
     */
    protected function isArrayOfUniformObjects(array $array): bool
    {
        // Very small lists are intentionally left in expanded form for readability.
        $min = (int) $this->config['min_rows_to_tabular'];
        if (count($array) < $min) {
            return false;
        }

        $firstKeys = null;
        foreach ($array as $item) {
            // Only associative arrays are eligible for tabular rendering.
            if (!is_array($item) || $this->isSequentialArray($item)) {
                return false;
            }

            // Field order must match exactly so row values stay aligned with the header.
            $keys = array_keys($item);
            if ($firstKeys === null) {
                $firstKeys = $keys;
            } elseif ($keys !== $firstKeys) {
                return false;
            }

            // Modern mode avoids table output when a cell would require nested structure.
            if (!$this->isLegacyMode() && !$this->rowContainsOnlyInlineValues($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether every row value can be emitted inline without data loss.
     *
     * @param array $row Candidate table row.
     * @return bool True when every value is scalar or null.
     */
    protected function rowContainsOnlyInlineValues(array $row): bool
    {
        foreach ($row as $value) {
            if (!$this->isScalar($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Escape inline string content according to the configured TOON rules.
     *
     * @param string $value Raw inline string.
     * @return string Escaped inline string.
     */
    protected function escapeInlineString(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        if ($this->isLegacyMode()) {
            $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        }

        if ($this->config['escape_style'] !== 'backslash') {
            return str_replace("\n", '\\n', $value);
        }

        $delimiter = $this->isLegacyMode() ? ',' : $this->getDelimiter();

        // Escape structural characters so inline text does not break parsing.
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace(':', '\\:', $value);

        if ($delimiter === "\t") {
            $value = str_replace("\t", '\\t', $value);
        } else {
            $value = str_replace($delimiter, '\\' . $delimiter, $value);
        }

        return str_replace("\n", '\\n', $value);
    }

    /**
     * Resolve the configured table delimiter.
     *
     * @return string Single-character delimiter used in TOON tables.
     */
    protected function getDelimiter(): string
    {
        return match ((string) $this->config['delimiter']) {
            'comma' => ',',
            'pipe' => '|',
            'tab' => "\t",
            default => (string) $this->config['delimiter'] !== '' ? (string) $this->config['delimiter'] : ',',
        };
    }

    /**
     * Determine whether the converter should preserve legacy output behavior.
     */
    protected function isLegacyMode(): bool
    {
        return strtolower((string) $this->config['compatibility_mode']) === 'legacy';
    }
}
