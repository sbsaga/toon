<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Converters;

use Sbsaga\Toon\Exceptions\ToonException;

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
     * Convert arbitrary input (json string, array, object, scalar) to TOON string.
     */
    public function toToon(mixed $input): string
    {
        // if string that looks like JSON try decode first
        if (is_string($input) && $this->looksLikeJson($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->valueToToon($decoded);
            }
            // fallthrough -> treat as free text
        }

        if (is_object($input)) {
            $input = json_decode(json_encode($input), true);
        }

        if (is_array($input) || $input instanceof \Traversable) {
            return $this->valueToToon((array) $input);
        }

        return $this->textToToon((string) $input);
    }

    /**
     * Recursive conversion for arrays and scalars.
     */
    protected function valueToToon(mixed $value, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);

        if (is_array($value)) {
            // sequential array (list)
            if ($this->isSequentialArray($value)) {
                // array of uniform objects -> tabular representation
                if ($this->isArrayOfUniformObjects($value)) {
                    return $this->arrayOfObjectsToToon($value, $depth);
                }

                // otherwise, render each item as a block line (nested)
                $lines = [];
                foreach ($value as $item) {
                    // when item is scalar, render inline at increased depth
                    if ($this->isScalar($item)) {
                        $lines[] = $indent . $this->inlineScalar($item);
                    } else {
                        $lines[] = $indent . $this->valueToToon($item, $depth + 1);
                    }
                }
                return implode("\n", $lines);
            }

            // associative object
            ksort($value, SORT_STRING);
            $lines = [];
            foreach ($value as $k => $v) {
                $safeKey = $this->safeKey((string)$k);
                if ($this->isScalar($v)) {
                    $lines[] = $indent . "{$safeKey}: " . $this->inlineScalar($v);
                } else {
                    $lines[] = $indent . "{$safeKey}:";
                    $lines[] = $this->valueToToon($v, $depth + 1);
                }
            }
            return implode("\n", $lines);
        }

        // fallback scalar
        return $indent . $this->inlineScalar($value);
    }

    /**
     * Create compact table block for uniform object list.
     *
     * Format:
     * items[N]{field1,field2}:
     *   v1,v2
     *   v3,v4
     */
    protected function arrayOfObjectsToToon(array $arr, int $depth = 0): string
    {
        if (empty($arr)) {
            return str_repeat('  ', $depth) . 'items[0]{}:';
        }

        $first = (array)$arr[0];
        $fields = array_keys($first);
        sort($fields, SORT_STRING);
        $indent = str_repeat('  ', $depth);

        $header = $indent . 'items[' . count($arr) . ']{' . implode(',', $fields) . '}:';

        $rows = [];
        $max = min(count($arr), (int)$this->config['max_preview_items']);
        for ($i = 0; $i < $max; $i++) {
            $rowItems = [];
            foreach ($fields as $f) {
                $rowItems[] = $this->inlineScalar($arr[$i][$f] ?? null);
            }
            $rows[] = $indent . '  ' . implode(',', $rowItems);
        }

        return $header . "\n" . implode("\n", $rows);
    }

    /**
     * Inline scalar formatting and escaping according to configured escape_style.
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

        $s = (string)$v;
        // trim and collapse whitespace to single spaces (keeps TOON readable)
        $s = trim(preg_replace('/\s+/', ' ', $s));

        if ($this->config['escape_style'] === 'backslash') {
            // escape backslash first, then special chars
            $s = str_replace('\\', '\\\\', $s);
            $s = str_replace(',', '\\,', $s);
            $s = str_replace(':', '\\:', $s);
            $s = str_replace("\n", '\\n', $s);
            return $s;
        }

        // default fallback: minimal escaping
        $s = str_replace("\n", '\\n', $s);
        return $s;
    }

    protected function textToToon(string $text): string
    {
        // treat as a single inline scalar
        return $this->inlineScalar($text);
    }

    protected function safeKey(string $k): string
    {
        // keep alnum, underscore, hyphen, dot; drop others; force lowercase for deterministic output
        $key = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $k);
        return strtolower($key);
    }

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

    protected function isArrayOfUniformObjects(array $arr): bool
    {
        $min = (int)$this->config['min_rows_to_tabular'];
        if (count($arr) < $min) {
            return false;
        }

        $firstKeys = null;
        foreach ($arr as $item) {
            if (!is_array($item)) {
                return false;
            }
            $k = array_keys($item);
            sort($k, SORT_STRING);
            if ($firstKeys === null) {
                $firstKeys = $k;
            } elseif ($k !== $firstKeys) {
                return false;
            }
        }
        return true;
    }
}
