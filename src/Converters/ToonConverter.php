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
     * Convert any input (array, object, JSON string) to TOON format.
     *
     * @param mixed $input
     * @return string
     */
    public function toToon(mixed $input): string
    {
        // If string looks like JSON, decode it
        if (is_string($input) && $this->looksLikeJson($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->valueToToon($decoded);
            }
        }

        // Convert objects to arrays
        if (is_object($input)) {
            $input = json_decode(json_encode($input), true);
        }

        if (is_array($input) || $input instanceof \Traversable) {
            return $this->valueToToon((array)$input);
        }

        // For scalar/string input
        return $this->textToToon((string)$input);
    }

    /**
     * Recursively convert value to TOON, supporting nested arrays/objects.
     */
    protected function valueToToon(mixed $value, int $depth = 0): string
    {
        $indent = str_repeat(' ', $depth);

        if (is_array($value)) {
            // Sequential numeric array
            if ($this->isSequentialArray($value)) {
                if ($this->isArrayOfUniformObjects($value)) {
                    return $this->arrayOfObjectsToToon($value, $depth);
                }

                $lines = [];
                foreach ($value as $item) {
                    $lines[] = $this->isScalar($item)
                        ? $indent . $this->inlineScalar($item)
                        : $this->valueToToon($item, $depth + 1);
                }

                return implode("\n", $lines);
            }

            // Associative array
            $lines = [];
            foreach ($value as $key => $val) {
                $safeKey = $this->safeKey((string)$key);
                if ($this->isScalar($val)) {
                    $lines[] = $indent . "{$safeKey}: " . $this->inlineScalar($val);
                } else {
                    $lines[] = $indent . "{$safeKey}:";
                    $lines[] = $this->valueToToon($val, $depth + 1);
                }
            }
            return implode("\n", $lines);
        }

        // Scalar value
        return $indent . $this->inlineScalar($value);
    }

    /**
     * Convert uniform array of objects to TOON table.
     */
    protected function arrayOfObjectsToToon(array $arr, int $depth = 0): string
    {
        if (empty($arr)) {
            return str_repeat(' ', $depth) . 'items[0]{}:';
        }

        $first = (array)$arr[0];
        $fields = array_keys($first);
        $indent = str_repeat(' ', $depth);
        $header = $indent . 'items[' . count($arr) . ']{' . implode(',', $fields) . '}:';

        $rows = [];
        $max = min(count($arr), (int)$this->config['max_preview_items']);
        for ($i = 0; $i < $max; $i++) {
            $row = [];
            foreach ($fields as $f) {
                $row[] = $this->inlineScalar($arr[$i][$f] ?? null);
            }
            $rows[] = $indent . ' ' . implode(',', $row);
        }

        return $header . "\n" . implode("\n", $rows);
    }

    /**
     * Convert scalar to TOON-compatible string.
     */
    protected function inlineScalar(mixed $v): string
    {
        if ($v === null) return '';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_int($v) || is_float($v)) return (string)$v;

        $s = trim(preg_replace('/\s+/', ' ', (string)$v));
        if ($this->config['escape_style'] === 'backslash') {
            $s = str_replace(['\\', ',', ':', "\n"], ['\\\\','\\,','\\:', '\\n'], $s);
        }

        return $s;
    }

    protected function textToToon(string $text): string
    {
        return $this->inlineScalar($text);
    }

    protected function safeKey(string $k): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9_\-\.]/', '', $k));
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

    /**
     * Check if array contains uniform objects (for table format)
     */
    protected function isArrayOfUniformObjects(array $arr): bool
    {
        $min = (int)$this->config['min_rows_to_tabular'];
        if (count($arr) < $min) return false;

        $firstKeys = null;
        foreach ($arr as $item) {
            if (!is_array($item)) return false;
            $keys = array_keys($item);
            if ($firstKeys === null) $firstKeys = $keys;
            elseif ($keys !== $firstKeys) return false;
        }

        return true;
    }
}
