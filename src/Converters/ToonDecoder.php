<?php

namespace Sbsaga\Toon\Converters;

class ToonDecoder
{
    /**
     * Parse a TOON string back into PHP array.
     * Note: this is a reasonably robust parser for the subset produced by ToonConverter.
     */
    public function fromToon(string $toon): array
    {
        $lines = preg_split("/\r?\n/", $toon);
        $stack = [[]];
        $indentStack = [0];

        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $indent = strlen($line) - strlen(ltrim($line, ' '));
            $content = trim($line);

            // Determine current depth by indent
            while (!empty($indentStack) && $indent < end($indentStack)) {
                array_pop($indentStack);
                $completed = array_pop($stack);
                $parent =& $stack[count($stack)-1];

                // if parent last key is placeholder for nested block, append completed appropriately
                // we keep structure simple: numeric arrays under their keys
                if (is_array($parent) && count($parent) > 0) {
                    end($parent);
                    $lastKey = key($parent);
                    if ($lastKey !== null && $parent[$lastKey] === null) {
                        $parent[$lastKey] = $completed;
                    }
                }
            }

            // Table header detection items[n]{a,b}:
            if (preg_match('/^items\[(\d+)\]\{([^\}]*)\}:$/', $content, $m)) {
                $count = (int)$m[1];
                $fields = array_map('trim', explode(',', $m[2]));
                // push placeholder to stack; next lines are rows
                $stack[] = ['__table__' => ['count' => $count, 'fields' => $fields, 'rows' => []]];
                $indentStack[] = $indent;
                continue;
            }

            // Table row: 1,Alice,true
            if (strpos($content, ',') !== false && preg_match('/^[0-9]+,/', $content)) {
                // find top of stack table container
                $top = &$stack[count($stack)-1];
                if (isset($top['__table__'])) {
                    $row = $this->splitCsvEscaped($content);
                    $fields = $top['__table__']['fields'];
                    $obj = [];
                    foreach ($fields as $i => $f) {
                        $obj[$f] = $row[$i] ?? '';
                    }
                    $top['__table__']['rows'][] = $obj;
                    continue;
                }
            }

            // key: value   or key:
            if (preg_match('/^([A-Za-z0-9_\-\.]+):(?:\s*(.*))?$/', $content, $mm)) {
                $key = $mm[1];
                $val = $mm[2] ?? null;

                $current =& $stack[count($stack)-1];
                if ($val === null || $val === '') {
                    // push placeholder null then a nested block will replace it
                    $current[$key] = null;
                    // create nested container
                    $stack[] = &$current[$key];
                    $indentStack[] = $indent + 2; // child indent expected
                } else {
                    // unescape inline value
                    $current[$key] = $this->unescape($val);
                }
                continue;
            }

            // If it's an indented row under top-of-stack table rows (two spaces etc)
            if (preg_match('/^[0-9]+,/', trim($content))) {
                // handled above; fallback ignore
                continue;
            }
        }

        // finalize - pop remaining tables
        while (count($stack) > 1) {
            $completed = array_pop($stack);
            $parent =& $stack[count($stack)-1];
            end($parent);
            $lastKey = key($parent);
            if ($lastKey !== null && $parent[$lastKey] === null) {
                $parent[$lastKey] = $completed;
            } else {
                // attach if parent is placeholder table
                if (isset($parent['__table__'])) {
                    // convert to actual array
                    $rows = $parent['__table__']['rows'];
                    $parent = $rows;
                }
            }
        }

        // If top contains a table placeholder, convert it
        $root = $stack[0];
        if (isset($root['__table__'])) {
            $root = $root['__table__']['rows'];
        }
        return $root;
    }

    /**
     * Split a CSV-like row where commas can be escaped with backslash.
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
        if ($current !== '') $result[] = $this->unescape($current);
        return $result;
    }

    /**
     * Unescape backslash escapes we added in inlineScalar/textToToon
     */
    protected function unescape(string $s): string
    {
        $s = str_replace('\\n', "\n", $s);
        $s = str_replace('\:', ':', $s);
        $s = str_replace('\,', ',', $s);
        // fix escaped backslash
        $s = str_replace('\\\\', '\\', $s);
        return $s;
    }
}
