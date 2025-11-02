<?php

namespace sbsaga\Toon\Converters;

class ToonConverter
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'min_rows_to_tabular' => 2,
            'max_preview_items' => 100,
        ], $config);
    }

    public function toToon(mixed $input): string
    {
        if (is_string($input) && $this->looksLikeJson($input)) {
            $decoded = json_decode($input, true);
            if ($decoded === null) return $this->textToToon($input);
            return $this->valueToToon($decoded);
        }

        if (is_array($input) || $input instanceof \Traversable) {
            return $this->valueToToon((array) $input);
        }

        if (is_object($input)) {
            return $this->valueToToon(json_decode(json_encode($input), true));
        }

        return $this->textToToon((string) $input);
    }

    protected function valueToToon($value, int $depth = 0): string
    {
        if (is_array($value)) {
            if ($this->isSequentialArray($value)) {
                if ($this->isArrayOfUniformObjects($value)) {
                    return $this->arrayOfObjectsToToon($value, $depth);
                }
                return implode("\n", array_map(fn($v) => $this->valueToToon($v, $depth + 1), $value));
            } else {
                $lines = [];
                foreach ($value as $k => $v) {
                    $indent = str_repeat('  ', $depth);
                    if ($this->isScalar($v)) {
                        $lines[] = $indent . $this->safeKey($k) . ': ' . $this->inlineScalar($v);
                    } else {
                        $lines[] = $indent . $this->safeKey($k) . ':';
                        $lines[] = $this->valueToToon($v, $depth + 1);
                    }
                }
                return implode("\n", $lines);
            }
        }

        return $this->textToToon((string) $value);
    }

    protected function arrayOfObjectsToToon(array $arr, int $depth = 0): string
    {
        $first = (array)($arr[0] ?? []);
        $fields = array_keys($first);
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

    protected function inlineScalar($v): string
    {
        if ($v === null) return '';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_numeric($v)) return (string) $v;

        $s = preg_replace('/\s+/', ' ', trim((string) $v));
        return str_replace(',', '\\u002C', $s);
    }

    protected function safeKey(string $k): string
    {
        return preg_replace('/[^A-Za-z0-9_\\-]/', '', $k);
    }

    protected function isScalar($v): bool
    {
        return is_null($v) || is_scalar($v);
    }

    protected function looksLikeJson(string $s): bool
    {
        $s = trim($s);
        return str_starts_with($s, '{') || str_starts_with($s, '[');
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
        foreach ($arr as $item) {
            if (array_keys((array)$item) !== $firstKeys) return false;
        }
        return true;
    }

    protected function textToToon(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
