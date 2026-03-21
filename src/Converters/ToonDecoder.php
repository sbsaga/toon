<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Converters;

use Sbsaga\Toon\Exceptions\ToonException;

/**
 * Parses TOON payloads into PHP arrays.
 *
 * ```php
 * $decoder = new ToonDecoder();
 *
 * $data = $decoder->fromToon(
 *     "items[2]{id,name}:\n  1,Alice\n  2,Bob"
 * );
 * ```
 */
class ToonDecoder
{
    /**
     * Runtime decoder configuration.
     *
     * @var array{
     *     coerce_scalar_types:bool,
     *     escape_style:string,
     *     delimiter:string,
     *     strict_mode:bool,
     *     compatibility_mode:string
     * }
     */
    protected array $config;

    /**
     * Create a new decoder instance.
     *
     * @param array $config Optional configuration overrides.
     */
    public function __construct(array $config = [])
    {
        // Merge caller-provided overrides with package defaults.
        $this->config = array_merge([
            'coerce_scalar_types' => true,
            'escape_style' => 'backslash',
            'delimiter' => 'comma',
            'strict_mode' => false,
            'compatibility_mode' => 'legacy',
        ], $config);
    }

    /**
     * Decode a TOON payload into nested PHP arrays.
     *
     * @param string $toon Serialized TOON payload.
     * @return array Decoded PHP representation.
     *
     * @throws ToonException When the parser encounters malformed strict-mode table data.
     */
    public function fromToon(string $toon): array
    {
        // Parsing is line-oriented so indentation can define nesting.
        $lines = preg_split("/\r?\n/", $toon);
        $root = [];

        // Keep parallel stacks for the active container, indentation depth, and keys seen per scope.
        $stack = [&$root];
        $indentStack = [-1];
        $seenKeysStack = [[]];

        foreach ($lines as $rawLine) {
            if ($rawLine === null) {
                continue;
            }

            $line = rtrim($rawLine, "\r\n");

            // Empty lines do not contribute structure.
            if (trim($line) === '') {
                continue;
            }

            // Leading spaces determine which nested container receives the current line.
            $indent = strlen($line) - strlen(ltrim($line, ' '));
            $content = trim($line);

            // Unwind parser state until the current line fits the active scope.
            while (count($indentStack) > 1 && $indent <= end($indentStack)) {
                array_pop($indentStack);
                array_pop($stack);
                array_pop($seenKeysStack);
            }

            $current = &$stack[count($stack) - 1];

            if (preg_match('/^items\[(\d+)\]\{(.*)\}:$/', $content, $matches)) {
                $expectedCount = (int) $matches[1];
                $fields = $this->parseFieldList($matches[2]);

                // Store table metadata until the row block has been fully parsed.
                $current[] = ['__table__' => [
                    'count' => $expectedCount,
                    'fields' => $fields,
                    'rows' => [],
                ]];

                // Table rows are parsed in their own nested context.
                $lastIndex = array_key_last($current);
                $stack[] = &$current[$lastIndex];
                $indentStack[] = $indent;
                $seenKeysStack[] = [];
                continue;
            }

            if (isset($current['__table__'])) {
                $rowText = trim($content);
                if ($rowText !== '') {
                    // Row cells follow escaped delimiter rules so separators can still appear in text values.
                    $rowCells = $this->splitDelimitedEscaped($rowText);
                    $fields = $current['__table__']['fields'];

                    if ($this->isStrictMode() && count($rowCells) !== count($fields)) {
                        throw new ToonException(sprintf(
                            'Table row width mismatch. Expected %d cells, received %d.',
                            count($fields),
                            count($rowCells)
                        ));
                    }

                    // Rebuild the row as an associative array using the declared field order.
                    $rowObject = [];
                    foreach ($fields as $index => $field) {
                        $rowObject[$field] = $this->coerceValue($rowCells[$index] ?? '');
                    }

                    // Append the parsed row and continue within the same table block.
                    $current['__table__']['rows'][] = $rowObject;
                    continue;
                }
            }

            if (preg_match('/^-\s*$/', $content)) {
                // A blank dash opens a complex list item.
                $current[] = [];
                $lastIndex = array_key_last($current);
                $stack[] = &$current[$lastIndex];
                $indentStack[] = $indent;
                $seenKeysStack[] = [];
                continue;
            }

            if (preg_match('/^-\s+(.+)$/', $content, $listMatch)) {
                // Inline list items are treated as scalar values.
                $current[] = $this->coerceValue($this->unescape($listMatch[1]));
                continue;
            }

            if (preg_match('/^([A-Za-z0-9_\-\.]+):(?:\s*(.*))?$/', $content, $keyMatch)) {
                $key = $this->normalizeKey($keyMatch[1]);
                $value = $keyMatch[2] ?? null;

                if ($this->isLegacyMode() && in_array($key, $seenKeysStack[count($seenKeysStack) - 1], true) && count($stack) > 1) {
                    // Legacy TOON uses repeated keys inside nested scopes to represent repeated records.
                    array_pop($stack);
                    $parent = &$stack[count($stack) - 1];
                    $parentKey = array_key_last($parent);

                    if ($parentKey !== null) {
                        if (!isset($parent[$parentKey][0]) || !is_array($parent[$parentKey][0])) {
                            $parent[$parentKey] = [$parent[$parentKey]];
                        }

                        $parent[$parentKey][] = [];
                        $lastIndex = array_key_last($parent[$parentKey]);
                        $stack[] = &$parent[$parentKey][$lastIndex];
                        $current = &$stack[count($stack) - 1];
                        $seenKeysStack[count($seenKeysStack) - 1] = [];
                    }
                }

                $seenKeysStack[count($seenKeysStack) - 1][] = $key;

                if ($value === null || trim($value) === '') {
                    // A key with no inline value opens a nested block on the following lines.
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $indentStack[] = $indent;
                    $seenKeysStack[] = [];
                } else {
                    // Inline values are unescaped and then coerced when configured.
                    $current[$key] = $this->coerceValue($this->unescape($value));
                }

                continue;
            }

            // Non-key content at a deeper indentation level is treated as a list item.
            if ($indent > (end($indentStack) ?? -1)) {
                $current[] = $this->coerceValue($this->unescape($content));
                continue;
            }

            throw new ToonException("Malformed TOON line at indent {$indent}: {$content}");
        }

        return $this->finalizeTables($root, true);
    }

    /**
     * Replace internal table markers with their parsed row collections.
     *
     * @param array $node Parsed node tree.
     * @param bool $isRoot Whether the current node is the root document node.
     * @return array Normalized node tree.
     */
    protected function finalizeTables(array $node, bool $isRoot = false): array
    {
        if (!$this->isLegacyMode() && count($node) === 1 && isset($node[0]['__table__'])) {
            return $this->finalizeTableNode($node[0]['__table__']);
        }

        foreach ($node as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            // Replace the internal table placeholder with the row collection expected by callers.
            if (isset($value['__table__'])) {
                $node[$key] = $this->finalizeTableNode($value['__table__']);
                continue;
            }

            // Recurse into nested maps and lists until all placeholders are resolved.
            $node[$key] = $this->finalizeTables($value);
        }

        return $node;
    }

    /**
     * Finalize a parsed table placeholder into a plain list of rows.
     *
     * @param array{count:int,fields:array,rows:array} $table Table metadata.
     * @return array Finalized row list.
     */
    protected function finalizeTableNode(array $table): array
    {
        if ($this->isStrictMode() && count($table['rows']) !== $table['count']) {
            throw new ToonException(sprintf(
                'Table row count mismatch. Expected %d rows, received %d.',
                $table['count'],
                count($table['rows'])
            ));
        }

        return $table['rows'];
    }

    /**
     * Parse a field list from a TOON table header.
     *
     * @param string $fieldList Raw field list from the table header.
     * @return array<int,string> Normalized field names.
     */
    protected function parseFieldList(string $fieldList): array
    {
        if (trim($fieldList) === '') {
            return [];
        }

        if ($this->isLegacyMode()) {
            return array_values(array_filter(
                array_map('trim', explode(',', $fieldList)),
                fn (string $field): bool => $field !== ''
            ));
        }

        $delimiter = $this->detectDelimiter($fieldList);
        $parts = explode($delimiter, $fieldList);

        return array_values(array_map(
            fn (string $field): string => $this->normalizeKey(trim($field)),
            array_filter($parts, fn (string $field): bool => trim($field) !== '')
        ));
    }

    /**
     * Split a TOON table row while preserving escaped delimiters.
     *
     * @param string $value Raw row text.
     * @return array Parsed cell values.
     */
    protected function splitDelimitedEscaped(string $value): array
    {
        $delimiter = $this->detectDelimiter($value);
        $pattern = '/(?<!\\\\)' . preg_quote($delimiter, '/') . '/';
        $parts = preg_split($pattern, $value);

        if ($parts === false) {
            return [$this->unescape(trim($value))];
        }

        return array_map(
            fn (string $part): string => $this->unescape(trim($part)),
            $parts
        );
    }

    /**
     * Resolve the delimiter that appears to be in use for the current fragment.
     *
     * @param string $value Raw TOON fragment.
     * @return string Single-character delimiter.
     */
    protected function detectDelimiter(string $value): string
    {
        if ($this->isLegacyMode()) {
            return ',';
        }

        $configured = $this->configuredDelimiter();

        if (str_contains($value, $configured)) {
            return $configured;
        }

        foreach ([',', '|', "\t"] as $candidate) {
            if (str_contains($value, $candidate)) {
                return $candidate;
            }
        }

        return $configured;
    }

    /**
     * Restore escaped control sequences used by the encoder.
     *
     * @param string $value Escaped TOON fragment.
     * @return string Unescaped value.
     */
    protected function unescape(string $value): string
    {
        if ($this->isLegacyMode()) {
            if ($this->config['escape_style'] === 'backslash') {
                return str_replace(['\\n', '\\:', '\\,', '\\\\'], ["\n", ':', ',', '\\'], $value);
            }

            return str_replace('\\n', "\n", $value);
        }

        if ($this->config['escape_style'] !== 'backslash') {
            return str_replace(['\\n', '\\t'], ["\n", "\t"], $value);
        }

        $placeholder = "\0backslash\0";
        $value = str_replace('\\\\', $placeholder, $value);
        $value = str_replace(['\\n', '\\t', '\\:', '\\,', '\\|'], ["\n", "\t", ':', ',', '|'], $value);

        $configured = $this->configuredDelimiter();
        if (!in_array($configured, [',', '|', "\t"], true)) {
            $value = str_replace('\\' . $configured, $configured, $value);
        }

        return str_replace($placeholder, '\\', $value);
    }

    /**
     * Convert scalar text to native PHP types when coercion is enabled.
     *
     * @param string $value Raw scalar text.
     * @return mixed Native PHP value or the original string.
     */
    protected function coerceValue(string $value): mixed
    {
        $trimmed = trim($value);

        // Empty cells decode to null so blank values survive round trips.
        if ($trimmed === '') {
            return null;
        }

        if ($this->config['coerce_scalar_types']) {
            $lower = strtolower($trimmed);
            if ($lower === 'true') {
                return true;
            }

            if ($lower === 'false') {
                return false;
            }

            if ($lower === 'null') {
                return null;
            }

            if (is_numeric($trimmed)) {
                // Preserve decimal values as floats and whole numbers as integers.
                return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
            }
        }

        return $trimmed;
    }

    /**
     * Normalize decoded key names according to the selected compatibility mode.
     *
     * @param string $key Raw key text from TOON.
     * @return string Normalized key name.
     */
    protected function normalizeKey(string $key): string
    {
        if ($this->isLegacyMode()) {
            return strtolower($key);
        }

        $normalized = preg_replace('/\s+/', '_', trim($key)) ?? '';
        $normalized = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $normalized) ?? '';

        return $normalized !== '' ? $normalized : 'field';
    }

    /**
     * Resolve the configured delimiter.
     *
     * @return string Single-character delimiter used in TOON tables.
     */
    protected function configuredDelimiter(): string
    {
        return match ((string) $this->config['delimiter']) {
            'comma' => ',',
            'pipe' => '|',
            'tab' => "\t",
            default => (string) $this->config['delimiter'] !== '' ? (string) $this->config['delimiter'] : ',',
        };
    }

    /**
     * Determine whether the decoder should preserve legacy key normalization.
     */
    protected function isLegacyMode(): bool
    {
        return strtolower((string) $this->config['compatibility_mode']) === 'legacy';
    }

    /**
     * Determine whether strict decoding validation is enabled.
     */
    protected function isStrictMode(): bool
    {
        return (bool) $this->config['strict_mode'];
    }
}
