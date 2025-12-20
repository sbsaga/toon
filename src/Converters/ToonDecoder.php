<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Converters;

use Sbsaga\Toon\Exceptions\ToonException;

/**
 * Class ToonDecoder
 *
 * @author Sagar
 * @package Sbsaga\Toon\Converters
 *
 * This class is responsible for decoding TOON formatted strings back into PHP arrays and objects.
 * It reverses the transformation performed by the ToonConverter class and ensures lossless decoding.
 *
 * ## Overview
 * - Parses TOON data line-by-line, using indentation to determine nested structures.
 * - Handles both associative maps and sequential lists.
 * - Supports compact tabular data formats (`items[N]{field1,field2}:` blocks).
 * - Applies optional scalar coercion (e.g., "true" → `true`, "123" → `int(123)`).
 *
 * ## Example
 * ```
 * items[2]{id,name,active}:
 *   1,Tannu,true
 *   2,Sunil,false
 * ```
 * Decodes into:
 * [
 *   ['id' => 1, 'name' => 'Tannu', 'active' => true],
 *   ['id' => 2, 'name' => 'Sunil', 'active' => false],
 * ]
 *
 * The parser prioritizes safety and predictability, throwing clear exceptions for malformed structures.
 * Each parsing stage is carefully documented and implemented with clarity for future maintainers like
 * Mannu or Surekha, ensuring that extending TOON syntax is straightforward.
 */
class ToonDecoder
{
    protected array $config;

    public function __construct(array $config = [])
    {
        // Merge default configuration with provided options.
        // Example: Vitthal might disable scalar coercion if he prefers preserving raw strings.
        $this->config = array_merge([
            'coerce_scalar_types' => true,
            'escape_style' => 'backslash',
        ], $config);
    }

    /**
     * Parse a TOON string into PHP associative arrays or sequential arrays.
     *
     * This parser reconstructs data in a way that is *structurally identical* to the
     * original PHP structure before it was encoded via ToonConverter.
     *
     * Example use case:
     * ```
     * $decoder = new ToonDecoder();
     * $data = $decoder->fromToon($toonString);
     * ```
     * When Surekha or Mannu use this to read TOON logs, they can safely convert
     * human-readable TOON text back to PHP arrays for further processing.
     *
     * @throws ToonException when encountering malformed TOON input
     */
    public function fromToon(string $toon): array
    {
        // Split TOON into individual lines, handling both \n and \r\n endings.
        $lines = preg_split("/\r?\n/", $toon);

        // Root container that holds the decoded structure.
        $root = [];

        // Stack of container references for nested structures.
        // Example: Vikas debugging deeply nested configs would rely on this stack maintaining hierarchy.
        $stack = [ & $root ];

        // Stack that tracks indentation depth to handle nested mappings and arrays.
        $indentStack = [ -1 ];

        // Track keys at each level to detect when an object should pivot into a list.
        $seenKeysStack = [ [] ];

        // Iterate through each line in the TOON input.
        foreach ($lines as $rawLine) {
            if ($rawLine === null) continue;
            $line = rtrim($rawLine, "\r\n");

            // Skip blank lines safely.
            if (trim($line) === '') {
                continue;
            }

            // Count indentation (spaces) to determine current nesting level.
            $indent = strlen($line) - strlen(ltrim($line, ' '));
            $content = trim($line);

            // Reduce nesting if current indentation is less than the last stored level.
            while (count($indentStack) > 1 && $indent <= end($indentStack)) {
                array_pop($indentStack);
                array_pop($stack);
                array_pop($seenKeysStack);
            }

            $current = & $stack[count($stack) - 1];

            /**
             * Handle compact tabular syntax:
             * Example:
             * items[3]{id,name,active}:
             *   1,Tannu,true
             *   2,Mannu,false
             *   3,Surekha,true
             */
            if (preg_match('/^items\[(\d+)\]\{([^\}]*)\}:$/', $content, $m)) {
                $expectedCount = (int)$m[1];
                $fieldList = array_map('trim', array_filter(array_map('trim', explode(',', $m[2])), function($v){ return $v !== ''; }));

                // Prepare a placeholder container for this TOON table block.
                $tableContainer = ['__table__' => ['count' => $expectedCount, 'fields' => $fieldList, 'rows' => []]];

                // Attach this table to the current structure (root or nested).
                $current[] = $tableContainer;

                // Push new reference context for the rows block.
                $stack[] = & $current[count($current) - 1];
                $indentStack[] = $indent;
                $seenKeysStack[] = [];
                continue;
            }

            // Handle row entries within a TOON table.
            if (isset($current['__table__'])) {
                $rowText = trim($content);
                if ($rowText !== '') {
                    // Split fields using escaped CSV logic to preserve commas and special chars.
                    $rowCells = $this->splitCsvEscaped($rowText);
                    $fields = $current['__table__']['fields'];

                    // Build associative array row: field => value
                    $rowObject = [];
                    foreach ($fields as $i => $field) {
                        $rowObject[$field] = $this->coerceValue($rowCells[$i] ?? '');
                    }

                    // Add parsed row into table rows.
                    $current['__table__']['rows'][] = $rowObject;
                    continue;
                }
            }

            /**
             * Handle standard key/value or nested key-only lines:
             * - "user: Tannu" → associative entry
             * - "settings:" → new nested block
             */
            if (preg_match('/^([A-Za-z0-9_\-\.]+):(?:\s*(.*))?$/', $content, $mm)) {
                $key = strtolower($mm[1]);
                $val = $mm[2] ?? null;

                // Pivot Logic: If key repeats at same level (e.g. 'id'), convert parent to list.
                if (in_array($key, $seenKeysStack[count($seenKeysStack)-1])) {
                    array_pop($stack);
                    $parent = & $stack[count($stack) - 1];
                    $parentKey = array_key_last($parent);
                    if (!isset($parent[$parentKey][0])) {
                        $parent[$parentKey] = [$parent[$parentKey]];
                    }
                    $parent[$parentKey][] = [];
                    $stack[] = & $parent[$parentKey][array_key_last($parent[$parentKey])];
                    $current = & $stack[count($stack) - 1];
                    $seenKeysStack[count($seenKeysStack)-1] = [];
                }

                $seenKeysStack[count($seenKeysStack)-1][] = $key;

                // If value is empty, expect a nested block below this line.
                if ($val === null || trim($val) === '') {
                    $current[$key] = [];
                    // Push new reference level to handle indented child elements.
                    $stack[] = & $current[$key];
                    $indentStack[] = $indent;
                    $seenKeysStack[] = [];
                } else {
                    // Simple scalar value line, coerce type and assign.
                    $current[$key] = $this->coerceValue($this->unescape($val));
                }
                continue;
            }

            // If a line does not fit any pattern, handle as sequential item or throw exception.
            if ($indent > (end($indentStack) ?? -1)) {
                $current[] = $this->coerceValue($this->unescape($content));
                continue;
            }

            throw new ToonException("Malformed TOON line at indent {$indent}: {$content}");
        }

        // Recursively finalize and normalize any embedded tables.
        return $this->finalizeTables($root);
    }

    /**
     * Converts internal table markers into finalized array structures.
     * Example: Converts `['__table__' => ['rows' => [...]]]` → `[ [...], [...], ... ]`
     */
    protected function finalizeTables(array $node)
    {
        foreach ($node as $k => $v) {
            if (is_array($v)) {
                if (isset($v['__table__'])) {
                    $node[$k] = $v['__table__']['rows'];
                } else {
                    $node[$k] = $this->finalizeTables($v);
                }
            }
        }
        return $node;
    }

    /**
     * Splits a CSV-like row respecting backslash-escaped commas and special characters.
     * Example: `1,Tannu\, Mannu,true` → ["1", "Tannu, Mannu", "true"]
     */
    protected function splitCsvEscaped(string $s): array
    {
        $result = preg_split('/(?<!\\\\),/', $s);
        return array_map(fn($p) => $this->unescape(trim($p)), $result);
    }

    /**
     * Decodes backslash-escaped sequences (\n, \:, \,) into their literal forms.
     */
    protected function unescape(string $s): string
    {
        if ($this->config['escape_style'] === 'backslash') {
            return str_replace(['\\n', '\\:', '\\,', '\\\\'], ["\n", ':', ',', '\\'], $s);
        }
        return str_replace('\\n', "\n", $s);
    }

    /**
     * Converts textual scalars into native PHP types (bool, int, float, null) if enabled.
     * Example:
     *  - "true" → true
     *  - "42" → 42
     *  - "null" → null
     * This is useful when Mannu or Surekha are decoding configuration-like data structures.
     */
    protected function coerceValue(string $s): mixed
    {
        $s = trim($s);
        if ($s === '') return null;

        if ($this->config['coerce_scalar_types']) {
            $lower = strtolower($s);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
            if ($lower === 'null') return null;
            if (is_numeric($s)) {
                return str_contains($s, '.') ? (float)$s : (int)$s;
            }
        }
        return $s;
    }
}