<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Converters;

use Sbsaga\Toon\Exceptions\ToonException;

class ToonDecoder
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'coerce_scalar_types' => true,
            'escape_style' => 'backslash',
        ], $config);
    }

    /**
     * Decode TOON string into PHP array
     *
     * @param string $toon
     * @return array
     * @throws ToonException
     */
    public function fromToon(string $toon): array
    {
        $lines = preg_split("/\r?\n/", $toon);
        $root = [];
        $stack = [&$root];
        $indentStack = [0];

        foreach ($lines as $rawLine) {
            if ($rawLine === null) continue;

            $line = rtrim($rawLine, "\r\n");
            if (trim($line) === '') continue;

            $indent = strlen($line) - strlen(ltrim($line, ' '));
            $content = trim($line);

            // Reduce stack based on indentation
            while (count($indentStack) > 0 && $indent < end($indentStack)) {
                array_pop($indentStack);
                array_pop($stack);
            }

            // Handle tabular items: items[COUNT]{fields}:
            if (preg_match('/^items\[(\d+)\]\{([^\}]*)\}:$/', $content, $m)) {
                $expectedCount = (int)$m[1];
                $fields = array_filter(array_map('trim', explode(',', $m[2])), fn($v) => $v !== '');
                $table = ['__table__' => ['count' => $expectedCount, 'fields' => $fields, 'rows' => []]];

                $current = &$stack[count($stack) - 1];
                $current[] = $table;

                $stack[] = &$current[count($current) - 1];
                $indentStack[] = $indent;
                continue;
            }

            // Handle rows inside a table
            $top = &$stack[count($stack) - 1];
            if (isset($top['__table__'])) {
                $rowText = trim($content);
                if ($rowText !== '') {
                    $rowCells = $this->splitCsvEscaped($rowText);
                    $fields = $top['__table__']['fields'];
                    $rowObj = [];
                    foreach ($fields as $i => $field) {
                        $rowObj[$field] = $this->coerceValue($rowCells[$i] ?? '');
                    }
                    $top['__table__']['rows'][] = $rowObj;
                    continue;
                }
            }

            // Normal key-value or nested
            if (preg_match('/^([A-Za-z0-9_\-\.]+):(?:\s*(.*))?$/', $content, $mm)) {
                $key = $mm[1];
                $val = $mm[2] ?? null;
                $current = &$stack[count($stack) - 1];

                if ($val === null || $val === '') {
                    $placeholderIndex = $this->attachPlaceholder($current, $key);
                    $stack[] = &$current[$placeholderIndex];
                    $indentStack[] = $indent + 2;
                } else {
                    $current[$key] = $this->coerceValue($this->unescape($val));
                }
                continue;
            }

            throw new ToonException("Malformed TOON line at indent {$indent}: {$content}");
        }

        return $this->finalizeNode($root);
    }

    /**
     * Attach a placeholder for nested structure
     */
    protected function attachPlaceholder(array &$container, string $key)
    {
        if (empty($container) || array_keys($container) === range(0, count($container) - 1)) {
            // Numeric array
            $container[] = [];
            end($container);
            return key($container);
        }

        // Associative
        $container[$key] = [];
        return $key;
    }

    /**
     * Finalize nodes: convert table containers to arrays
     */
    protected function finalizeNode(array $node): array
    {
        foreach ($node as $k => $v) {
            if (is_array($v)) {
                if ($this->isTableContainer($v)) {
                    $node[$k] = $this->extractTableRows($v);
                } else {
                    $node[$k] = $this->finalizeNode($v);
                }
            }
        }

        return $node;
    }

    /**
     * Check if a node is a table container
     */
    protected function isTableContainer(array $arr): bool
    {
        if (isset($arr['__table__'])) return true;
        if (count($arr) === 1 && isset(reset($arr)['__table__'])) return true;
        return false;
    }

    /**
     * Extract rows from table container
     */
    protected function extractTableRows(array $container): array
    {
        if (isset($container['__table__'])) return $container['__table__']['rows'];
        if (count($container) === 1 && isset(reset($container)['__table__'])) {
            return reset($container)['__table__']['rows'];
        }
        return $container;
    }

    /**
     * Split CSV respecting escaped commas
     */
    protected function splitCsvEscaped(string $s): array
    {
        $result = [];
        $current = '';
        $len = strlen($s);

        for ($i = 0; $i < $len; $i++) {
            $ch = $s[$i];

            if ($ch === '\\' && $i + 1 < $len) {
                $current .= $s[$i + 1];
                $i++;
                continue;
            }

            if ($ch === ',') {
                $result[] = $this->unescape($current);
                $current = '';
                continue;
            }

            $current .= $ch;
        }

        $result[] = $this->unescape($current);
        return $result;
    }

    /**
     * Unescape backslash sequences
     */
    protected function unescape(string $s): string
    {
        if ($this->config['escape_style'] === 'backslash') {
            return str_replace(['\\n','\\:', '\\,','\\\\'], ["\n",':',',','\\'], $s);
        }
        return str_replace('\\n', "\n", $s);
    }

    /**
     * Convert string to scalar type if needed
     */
    protected function coerceValue(string $s): mixed
    {
        $s = trim($s);

        if ($this->config['coerce_scalar_types']) {
            if ($s === '') return null;
            $lower = strtolower($s);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
            if ($lower === 'null') return null;

            if (preg_match('/^[+-]?\d+$/', $s)) return (int)$s;
            if (is_numeric($s)) return (float)$s + 0;
        }

        return $s;
    }
}
