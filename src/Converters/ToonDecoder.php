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
        $indentStack = [ 0 ];

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
            while (count($indentStack) > 0 && $indent < end($indentStack)) {
                array_pop($indentStack);
                array_pop($stack);
            }

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
                $current = & $stack[count($stack) - 1];

                // Attach this table to the current structure (root or nested).
                $current[] = $tableContainer;

                // Push new reference context for the rows block.
                $stack[] = & $current[count($current) - 1];
                $indentStack[] = $indent;
                continue;
            }

            // Handle row entries within a TOON table.
            $top = & $stack[count($stack) - 1];
            if (isset($top['__table__'])) {
                $rowText = trim($content);
                if ($rowText !== '') {
                    // Split fields using escaped CSV logic to preserve commas and special chars.
                    $rowCells = $this->splitCsvEscaped($rowText);
                    $fields = $top['__table__']['fields'];

                    // Build associative array row: field => value
                    $rowObject = [];
                    foreach ($fields as $i => $field) {
                        $rowObject[$field] = $this->coerceValue($rowCells[$i] ?? '');
                    }

                    // Add parsed row into table rows.
                    $top['__table__']['rows'][] = $rowObject;
                    continue;
                }
            }

            /**
             * Handle standard key/value or nested key-only lines:
             * - "user: Tannu" → associative entry
             * - "settings:" → new nested block
             */
            if (preg_match('/^([A-Za-z0-9_\-\.]+):(?:\s*(.*))?$/', $content, $mm)) {
                $key = $mm[1];
                $val = $mm[2] ?? null;
                $current = & $stack[count($stack) - 1];

                // If value is empty, expect a nested block below this line.
                if ($val === null || $val === '') {
                    // Insert placeholder for nested section.
                    $placeholderIndex = $this->attachPlaceholder($current, $key);

                    // Push new reference level to handle indented child elements.
                    $stack[] = & $current[$placeholderIndex];
                    $indentStack[] = $indent + 2;
                } else {
                    // Simple scalar value line, coerce type and assign.
                    $current[$key] = $this->coerceValue($this->unescape($val));
                }
                continue;
            }

            // If a line does not fit any pattern, throw an exception.
            // This strictness helps maintain TOON’s predictability in production.
            throw new ToonException("Malformed TOON line at indent {$indent}: {$content}");
        }

        // Recursively finalize and normalize any embedded tables.
        $root = $this->finalizeTables($root);

        // If the root element itself is a table, return only its rows.
        if ($this->isTableContainer($root)) {
            return $this->extractTableRows($root);
        }

        // Return fully reconstructed PHP structure.
        return $root;
    }

    /**
     * Creates a placeholder entry for nested data structures.
     * Used internally when parsing key-only lines such as `user:` or `config:`.
     *
     * Example:
     * ```
     * config:
     *   api_key: 12345
     * ```
     *
     * @return string|int The array key or index where the placeholder resides.
     */
    protected function attachPlaceholder(array &$container, string $key)
    {
        // Detect whether current container is associative.
        $hasStringKey = false;
        foreach ($container as $k => $_) {
            if (!is_int($k)) { $hasStringKey = true; break; }
        }

        // For associative arrays, attach placeholder under the same key.
        if ($hasStringKey || empty($container)) {
            $container[$key] = [];
            return $key;
        }

        // For sequential arrays, push a new associative element with the given key.
        $container[] = [$key => []];
        end($container);
        return key($container);
    }

    /**
     * Converts internal table markers into finalized array structures.
     * Example: Converts `['__table__' => ['rows' => [...]]]` → `[ [...], [...], ... ]`
     */
    protected function finalizeTables(array $node)
    {
        if ($this->isTableContainer($node)) {
            return $this->extractTableRows($node);
        }

        foreach ($node as $k => $v) {
            if (is_array($v)) {
                $node[$k] = $this->finalizeTables($v);
            }
        }

        return $node;
    }

    /**
     * Determines whether the given node is a TOON table container.
     */
    protected function isTableContainer(array $arr): bool
    {
        if (isset($arr['__table__']) && is_array($arr['__table__'])) {
            return true;
        }

        // Handle single-element numeric arrays like `[ ['__table__' => ...] ]`
        if (count($arr) === 1) {
            $first = reset($arr);
            if (is_array($first) && isset($first['__table__'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extracts rows from a TOON table container.
     */
    protected function extractTableRows(array $container): array
    {
        if (isset($container['__table__'])) {
            return $container['__table__']['rows'];
        }

        if (count($container) === 1) {
            $first = reset($container);
            if (isset($first['__table__'])) {
                return $first['__table__']['rows'];
            }
        }

        return $container;
    }

    /**
     * Splits a CSV-like row respecting backslash-escaped commas and special characters.
     * Example: `1,Tannu\, Mannu,true` → ["1", "Tannu, Mannu", "true"]
     */
    protected function splitCsvEscaped(string $s): array
    {
        $result = [];
        $current = '';
        $len = strlen($s);

        for ($i = 0; $i < $len; $i++) {
            $ch = $s[$i];

            // Handle escape sequences (\, \:, \\)
            if ($ch === '\\' && $i + 1 < $len) {
                $current .= $s[$i + 1];
                $i++;
                continue;
            }

            // Split at commas unless escaped
            if ($ch === ',') {
                $result[] = $this->unescape($current);
                $current = '';
                continue;
            }

            $current .= $ch;
        }

        // Append last field
        $result[] = $this->unescape($current);
        return $result;
    }

    /**
     * Decodes backslash-escaped sequences (\n, \:, \,) into their literal forms.
     */
    protected function unescape(string $s): string
    {
        if ($this->config['escape_style'] === 'backslash') {
            $s = str_replace(['\\n', '\:', '\,', '\\\\'], ["\n", ':', ',', '\\'], $s);
            return $s;
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

        if ($this->config['coerce_scalar_types']) {
            if ($s === '') {
                return null;
            }

            $lower = strtolower($s);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
            if ($lower === 'null') return null;

            // Integer pattern (without decimals)
            if (preg_match('/^[+-]?\d+$/', $s)) {
                return (int)$s;
            }

            // Floating-point detection (e.g., "3.14", "-2.5")
            if (is_numeric($s)) {
                return (float)$s + 0;
            }
        }

        // Fallback: leave as string
        return $s;
    }
}
