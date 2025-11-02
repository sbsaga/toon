<?php

namespace Sbsaga\Toon\Converters;

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
     * Public entry to convert any input to TOON string.
     */
    public function toToon(mixed $input): string
    {
        if (is_string($input) && $this->looksLikeJson($input)) {
            $decoded = json_decode($input, true);
            if ($decoded === null) {
                return $this->textToToon($input);
            }
            return $this->valueToToon($decoded);
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
     * Convert a PHP value (array/scalar) recursively.
     */
    protected function valueToToon($value, int $depth = 0): string
    {
        if (is_array($value)) {
            // associative or sequential?
            if ($this->isSequentialArray($value)) {
                // sequence of uniform objects -> table
                if ($this->isArrayOfUniformObjects($value)) {
                    return $this->arrayOfObjectsToToon($value, $depth);
                }
                // fallback: list items each on new block
                $lines = [];
                foreach ($value as $item) {
                    $lines[] = str_repeat('  ', $depth) . $this->valueToToon($item, $depth + 1);
                }
                return implode("\n", $lines);
            }

            // associative object: sort keys deterministically
            ksort($value);
            $lines = [];
            foreach ($value as $k => $v) {
                $indent = str_repeat('  ', $depth);
                $key = $this->safeKey($k);
                if ($this->isScalar($v)) {
                    $lines[] = $indent . $key . ': ' . $this->inlineScalar($v);
                } else {
                    $lines[] = $indent . $key . ':';
                    $lines[] = $this->valueToToon($v, $depth + 1);
                }
            }
            return implode("\n", $lines);
        }

        return $this->textToToon((string) $value);
    }

    /**
     * Convert array of uniform objects into a compact table-like TOON.
     */
    protected function arrayOfObjectsToToon(array $arr, int $depth = 0): string
    {
        $first = (array)($arr[0] ?? []);
        $fields = array_keys($first);
        // deterministic fields order
        sort($fields, SORT_STRING);
        $indent = str_repeat('  ', $depth);

        $header = $indent . 'items[' . count($arr) . ']{' . implode(',', $fields) . '}:';
        $rows = [];
        $max = min(count($arr), $this->config['max_preview_items']);

        for ($i = 0; $i < $max; $i++) {
            $row = [];
            foreach ($fields as $f) {
                $row[] = $this->inlineScalar($arr[$i][$f] ?? '');
            }
            $rows[] = $indent . '  ' . implode(',', $row);
        }

        return $header . "\n" . implode("\n", $rows);
    }

    /**
     * Scalar inline formatting with escaping.
     */
    protected function inlineScalar($v): string
    {
        if ($v === null) return '';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_numeric($v)) return (string) $v;

        $s = trim((string)$v);
        // collapse whitespace
        $s = preg_replace('/\s+/', ' ', $s);

        // escape according to backslash style: \, \:, \\ and \n for newline
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace(',', '\\,', $s);
        $s = str_replace(':', '\\:', $s);
        $s = str_replace("\n", '\\n', $s);

        return $s;
    }

    protected function safeKey(string $k): string
    {
        // keep alnum, _ and - and dots; remove other characters; lowercase for determinism
        $key = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $k);
        return strtolower($key);
    }

    protected function isScalar($v): bool
    {
        return is_null($v) || is_scalar($v);
    }

    protected function looksLikeJson(string $s): bool
    {
        $s = trim($s);
        return $s === '' ? false : (str_starts_with($s, '{') || str_starts_with($s, '['));
    }

    protected function isSequentialArray(array $arr): bool
    {
        return array_values($arr) === $arr;
    }

    protected function isArrayOfUniformObjects(array $arr): bool
    {
        if (count($arr) < (int)$this->config['min_rows_to_tabular']) return false;
        foreach ($arr as $item) {
            if (!is_array($item)) return false;
        }
        $firstKeys = array_keys((array)$arr[0]);
        sort($firstKeys, SORT_STRING);
        foreach ($arr as $item) {
            $k = array_keys((array)$item);
            sort($k, SORT_STRING);
            if ($k !== $firstKeys) return false;
        }
        return true;
    }

    protected function textToToon(string $text): string
    {
        // simple inline escaping for free text if required
        $s = trim($text);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace(',', '\\,', $s);
        $s = str_replace(':', '\\:', $s);
        $s = str_replace("\n", '\\n', $s);
        return $s;
    }
}
