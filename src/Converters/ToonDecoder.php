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
     * Parse a TOON string into PHP structure (associative arrays and sequential arrays).
     *
     * This parser is defensive but tailored to the TOON syntax produced by ToonConverter.
     *
     * @throws ToonException on malformed input
     */
    public function fromToon(string $toon): array
    {
        $lines = preg_split("/\r?\n/", $toon);
        $root = [];
        $stack = [ & $root ]; // stack of references to containers
        $indentStack = [ 0 ]; // expected indent for children at each level

        foreach ($lines as $rawLine) {
            if ($rawLine === null) continue;
            $line = rtrim($rawLine, "\r\n");
            if (trim($line) === '') {
                continue;
            }

            $indent = strlen($line) - strlen(ltrim($line, ' '));
            $content = trim($line);

            // pop stacks while current indent indicates we've returned to parent
            while (count($indentStack) > 0 && $indent < end($indentStack)) {
                array_pop($indentStack);
                array_pop($stack);
            }

            // Table header items[N]{a,b}:
            if (preg_match('/^items\[(\d+)\]\{([^\}]*)\}:$/', $content, $m)) {
                $expectedCount = (int)$m[1];
                $fieldList = array_map('trim', array_filter(array_map('trim', explode(',', $m[2])), function($v){ return $v !== ''; }));
                // push a temporary container with __table__ marker
                $tableContainer = ['__table__' => ['count' => $expectedCount, 'fields' => $fieldList, 'rows' => []]];
                $current = & $stack[count($stack) - 1];
                // table should be attached either to a key in current associative array, or if current is root, then return rows directly
                // if current is associative array, we need to push this table as a value inside it later
                $current[] = $tableContainer;
                // push pointer to the new table container's rows for parsing rows easily
                $stack[] = & $current[count($current) - 1];
                $indentStack[] = $indent;
                continue;
            }

            // Table row detection: row lines typically start with number or any values separated by commas.
            // But to be safe, detect comma-separated content when top of stack is a table.
            $top = & $stack[count($stack) - 1];
            if (isset($top['__table__'])) {
                // table rows are expected to be at increased indent (child indent). Accept rows at current indent (most implementations indent with 2 spaces).
                $rowText = trim($content);
                // allow any row that contains a comma (fields separated) or a single scalar representing single-field table
                if ($rowText !== '') {
                    $rowCells = $this->splitCsvEscaped($rowText);
                    $fields = $top['__table__']['fields'];

                    $rowObject = [];
                    foreach ($fields as $i => $field) {
                        $rowObject[$field] = $this->coerceValue($rowCells[$i] ?? '');
                    }
                    $top['__table__']['rows'][] = $rowObject;
                    continue;
                }
            }

            // key: value  or key:
            if (preg_match('/^([A-Za-z0-9_\-\.]+):(?:\s*(.*))?$/', $content, $mm)) {
                $key = $mm[1];
                $val = $mm[2] ?? null;
                $current = & $stack[count($stack) - 1];

                // If val is empty string or not present -> nested block expected
                if ($val === null || $val === '') {
                    // create placeholder null value that will be replaced by nested container
                    // push placeholder, then push its reference onto stack
                    $placeholderIndex = $this->attachPlaceholder($current, $key);
                    $stack[] = & $current[$placeholderIndex];
                    $indentStack[] = $indent + 2; // child indent expected (two spaces)
                } else {
                    // inline scalar
                    $current[$key] = $this->coerceValue($this->unescape($val));
                }
                continue;
            }

            // If we reach here, the line is unexpected in current context — throw or ignore.
            // It's safer to throw in production to detect malformed TOON.
            throw new ToonException("Malformed TOON line at indent {$indent}: {$content}");
        }

        // finalize: convert any found table placeholders into arrays properly
        $root = $this->finalizeTables($root);

        // if root is a single numeric-indexed array containing a single table placeholder, return its rows directly
        if ($this->isTableContainer($root)) {
            return $this->extractTableRows($root);
        }

        return $root;
    }

    /**
     * Attach a placeholder entry for a nested block into the container and return its key/index reference.
     * If container is associative (has string keys), sets by key, otherwise appends and returns numeric index.
     *
     * @return mixed index/key where placeholder resides (string key or numeric index)
     */
    protected function attachPlaceholder(array &$container, string $key)
    {
        // If container appears associative (has string keys), attach as $container[$key] = []
        // Detect associative by checking if any string keys exist
        $hasStringKey = false;
        foreach ($container as $k => $_) {
            if (!is_int($k)) { $hasStringKey = true; break; }
        }

        if ($hasStringKey || empty($container)) {
            $container[$key] = [];
            return $key;
        }
        // otherwise push an associative object with the key (rare), but we keep behavior consistent
        $container[] = [$key => []];
        end($container);
        return key($container);
    }

    protected function finalizeTables(array $node)
    {
        // recursively convert any node containing __table__ placeholder into actual rows arrays
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

    protected function isTableContainer(array $arr): bool
    {
        // detect either an associative with __table__ key, or numeric list whose elements contain __table__ placeholder
        if (isset($arr['__table__']) && is_array($arr['__table__'])) {
            return true;
        }
        // also check single-element numeric arrays for placeholder
        if (count($arr) === 1) {
            $first = reset($arr);
            if (is_array($first) && isset($first['__table__'])) {
                return true;
            }
        }
        return false;
    }

    protected function extractTableRows(array $container): array
    {
        if (isset($container['__table__'])) {
            return $container['__table__']['rows'];
        }
        // single-element numeric container
        if (count($container) === 1) {
            $first = reset($container);
            if (isset($first['__table__'])) {
                return $first['__table__']['rows'];
            }
        }
        return $container;
    }

    /**
     * Split CSV-like row where commas may be escaped using backslash.
     */
    protected function splitCsvEscaped(string $s): array
    {
        $result = [];
        $current = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $ch = $s[$i];
            if ($ch === '\\' && $i + 1 < $len) {
                // append next char literally (handles \, \: \\ \n etc.)
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
        // push last
        $result[] = $this->unescape($current);
        return $result;
    }

    /**
     * Unescape according to backslash escape style.
     */
    protected function unescape(string $s): string
    {
        if ($this->config['escape_style'] === 'backslash') {
            // process common escapes
            $s = str_replace(['\\n', '\:', '\,', '\\\\'], ["\n", ':', ',', '\\'], $s);
            return $s;
        }
        // default minimal
        return str_replace('\\n', "\n", $s);
    }

    /**
     * Optionally coerce textual scalars to native PHP types (true/false/null/numeric) if configured.
     */
    protected function coerceValue(string $s): mixed
    {
        $s = trim($s);

        if ($this->config['coerce_scalar_types']) {
            if ($s === '') {
                return null;
            }

            // booleans
            $lower = strtolower($s);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
            if ($lower === 'null') return null;

            // integer
            if (preg_match('/^[+-]?\d+$/', $s)) {
                // safe cast
                return (int)$s;
            }

            // float
            if (is_numeric($s)) {
                return (float)$s + 0;
            }
        }

        return $s;
    }
}
