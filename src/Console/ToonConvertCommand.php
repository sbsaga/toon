<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Sbsaga\Toon\Exceptions\ToonException;

/**
 * Convert input between TOON and JSON through an Artisan command.
 *
 * ```bash
 * php artisan toon:convert storage/example.json --encode
 * php artisan toon:convert storage/example.toon --decode --pretty
 * ```
 */
class ToonConvertCommand extends Command
{
    /**
     * Console signature for the conversion command.
     */
    protected $signature = 'toon:convert
        {file? : Path to input file; if omitted reads STDIN}
        {--d|--decode : Decode TOON to JSON (default behavior when --decode set)}
        {--e|--encode : Encode JSON/PHP to TOON}
        {--o|--output= : Output file (if omitted prints to stdout)}
        {--p|--pretty : Pretty-print JSON when decoding}
        {--c|--config= : Optional path to a custom toon config file (php returning array)}';

    /**
     * Command description shown in Artisan help output.
     */
    protected $description = 'Encode (or decode) a file/string to/from TOON format.';

    /**
     * Execute the command.
     *
     * @param Filesystem $fs Filesystem implementation used for input and output operations.
     * @return int Process exit code.
     */
    public function handle(Filesystem $fs): int
    {
        // Resolve arguments and options once so the rest of the method can stay focused on I/O flow.
        $file = $this->argument('file');

        // Decode by default unless the caller explicitly requests encoding.
        $decode = $this->option('decode') || !$this->option('encode'); // defaults to decode mode
        $output = $this->option('output');
        $pretty = (bool)$this->option('pretty');
        $configPath = $this->option('config');

        // Allow a one-off config override without changing the application's published config file.
        if ($configPath) {
            if (!$fs->exists($configPath)) {
                $this->error("Config file not found: {$configPath}");
                return 2;
            }

            try {
                $custom = include $configPath;

                if (is_array($custom)) {
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
                // Read from a named file when the caller passes an explicit path.
                if (!$fs->exists($file)) {
                    $this->error("File not found: {$file}");
                    return 1;
                }

                $input = $fs->get($file);
            } else {
                // Fall back to STDIN so the command can participate in shell pipelines.
                $this->info('Reading from STDIN (press Ctrl+D or send EOF to end):');
                $input = stream_get_contents(STDIN);

                if ($input === false) {
                    $input = '';
                }
            }
        } catch (\Throwable $e) {
            $this->error("Failed to read input: " . $e->getMessage());
            return 1;
        }

        try {
            if ($decode) {
                // Decoding always produces JSON for CLI output, with pretty-printing as an option.
                $decoded = app('toon')->decode($input);
                $out = $pretty
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : json_encode($decoded);
            } else {
                // Encoding delegates format decisions to the core TOON service.
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
                // Persist the result when an output path is supplied.
                $fs->put($output, $out);
                $this->info("Saved to {$output}");
            } catch (\Throwable $e) {
                $this->error("Failed to write output file: " . $e->getMessage());
                return 5;
            }
        } else {
            // Default to stdout so the command remains script-friendly.
            $this->line($out);
        }

        return 0;
    }
}
