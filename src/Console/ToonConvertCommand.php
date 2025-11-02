<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Sbsaga\Toon\Exceptions\ToonException;

class ToonConvertCommand extends Command
{
    protected $signature = 'toon:convert
        {file? : Path to input file; if omitted reads STDIN}
        {--d|--decode : Decode TOON to JSON (default behavior when --decode set)}
        {--e|--encode : Encode JSON/PHP to TOON}
        {--o|--output= : Output file (if omitted prints to stdout)}
        {--p|--pretty : Pretty-print JSON when decoding}
        {--c|--config= : Optional path to a custom toon config file (php returning array)}';

    protected $description = 'Encode (or decode) a file/string to/from TOON format.';

    public function handle(Filesystem $fs): int
    {
        $file = $this->argument('file');
        $decode = $this->option('decode') || !$this->option('encode'); // default to decode when not explicit encode
        $output = $this->option('output');
        $pretty = (bool)$this->option('pretty');
        $configPath = $this->option('config');

        // optionally load custom config (array)
        if ($configPath) {
            if (!$fs->exists($configPath)) {
                $this->error("Config file not found: {$configPath}");
                return 2;
            }
            try {
                /** @noinspection PhpIncludeInspection */
                $custom = include $configPath;
                if (is_array($custom)) {
                    // merge with Laravel config if present; otherwise set into app container if exists
                    if (function_exists('config')) {
                        config(['toon' => array_merge(config('toon', []), $custom)]);
                    }
                }
            } catch (\Throwable $e) {
                $this->error("Failed to load config: " . $e->getMessage());
                return 2;
            }
        }

        $input = null;
        try {
            if ($file) {
                if (!$fs->exists($file)) {
                    $this->error("File not found: {$file}");
                    return 1;
                }
                $input = $fs->get($file);
            } else {
                // read from STDIN
                $this->info('Reading from STDIN (press Ctrl+D or send EOF to end):');
                $input = stream_get_contents(STDIN);
                if ($input === false) $input = '';
            }
        } catch (\Throwable $e) {
            $this->error("Failed to read input: " . $e->getMessage());
            return 1;
        }

        try {
            if ($decode) {
                $decoded = app('toon')->decode($input);
                $out = $pretty ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : json_encode($decoded);
            } else {
                $out = app('toon')->convert($input);
            }
        } catch (ToonException $e) {
            $this->error('TOON parsing/serialization error: ' . $e->getMessage());
            return 3;
        } catch (\Throwable $e) {
            $this->error('Unexpected error: ' . $e->getMessage());
            return 4;
        }

        if ($output) {
            try {
                $fs->put($output, $out);
                $this->info("Saved to {$output}");
            } catch (\Throwable $e) {
                $this->error("Failed to write output file: " . $e->getMessage());
                return 5;
            }
        } else {
            $this->line($out);
        }

        return 0;
    }
}
