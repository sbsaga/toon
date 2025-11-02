<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Sbsaga\Toon\Exceptions\ToonException;

/**
 * --------------------------------------------------------------------------
 * 🎯 ToonConvertCommand
 * --------------------------------------------------------------------------
 * Laravel Artisan command to encode or decode files between
 * JSON and TOON (Token-Optimized Object Notation) formats.
 *
 * This command allows developers and AI engineers to convert
 * structured data directly from the terminal, making it easy
 * to test, debug, or prepare LLM-friendly prompts.
 *
 * 🧩 Examples:
 *
 *  ▶ Convert JSON to TOON
 *    php artisan toon:convert storage/test.json
 *
 *  ▶ Decode TOON back to JSON
 *    php artisan toon:convert storage/test.toon --decode
 *
 *  ▶ Pretty-print decoded JSON
 *    php artisan toon:convert storage/test.toon --decode --pretty
 *
 *  ▶ Custom configuration path
 *    php artisan toon:convert data/sample.json --config=config/custom_toon.php
 *
 * 💡 Author: Sagar Bhedodkar
 * 🔖 License: MIT
 * --------------------------------------------------------------------------
 */
class ToonConvertCommand extends Command
{
    /**
     * Artisan command signature defining available arguments and options.
     *
     * - file: Optional input file (reads STDIN if not provided)
     * - --decode / -d: Convert TOON → JSON
     * - --encode / -e: Convert JSON → TOON
     * - --output / -o: Path to save output (default: print to stdout)
     * - --pretty / -p: Pretty-print JSON when decoding
     * - --config / -c: Path to custom config file
     *
     * @var string
     */
    protected $signature = 'toon:convert
        {file? : Path to input file; if omitted reads STDIN}
        {--d|--decode : Decode TOON to JSON (default behavior when --decode set)}
        {--e|--encode : Encode JSON/PHP to TOON}
        {--o|--output= : Output file (if omitted prints to stdout)}
        {--p|--pretty : Pretty-print JSON when decoding}
        {--c|--config= : Optional path to a custom toon config file (php returning array)}';

    /**
     * Short command description visible in `php artisan list`.
     *
     * @var string
     */
    protected $description = 'Encode (or decode) a file/string to/from TOON format.';

    /**
     * Handle the command execution.
     *
     * Reads a JSON or TOON file (or STDIN), converts it accordingly,
     * and outputs the result either to console or a specified file.
     *
     * @param  Filesystem  $fs
     * @return int  Exit code (0 = success, non-zero = failure)
     */
    public function handle(Filesystem $fs): int
    {
        // Extract command arguments and options
        $file = $this->argument('file');
        $decode = $this->option('decode') || !$this->option('encode'); // Default to decode mode
        $output = $this->option('output');
        $pretty = (bool)$this->option('pretty');
        $configPath = $this->option('config');

        /**
         * -----------------------------------------------------------
         * 🔧 Step 1: Load custom configuration (if provided)
         * -----------------------------------------------------------
         * Allows overriding the default TOON configuration by specifying
         * a PHP file returning an array. Example:
         *
         * return ['min_rows_to_tabular' => 5, 'escape_style' => 'json'];
         */
        if ($configPath) {
            if (!$fs->exists($configPath)) {
                $this->error("Config file not found: {$configPath}");
                return 2;
            }

            try {
                /** @noinspection PhpIncludeInspection */
                $custom = include $configPath;
                if (is_array($custom)) {
                    // Merge with Laravel config if available
                    if (function_exists('config')) {
                        config(['toon' => array_merge(config('toon', []), $custom)]);
                    }
                }
            } catch (\Throwable $e) {
                $this->error("Failed to load config: " . $e->getMessage());
                return 2;
            }
        }

        /**
         * -----------------------------------------------------------
         * 📥 Step 2: Read Input (from file or STDIN)
         * -----------------------------------------------------------
         * Supports reading from either:
         *   - A specified file path
         *   - STDIN (useful for piping data in shell)
         */
        $input = null;
        try {
            if ($file) {
                if (!$fs->exists($file)) {
                    $this->error("File not found: {$file}");
                    return 1;
                }
                $input = $fs->get($file);
            } else {
                // Interactive STDIN mode
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

        /**
         * -----------------------------------------------------------
         * ⚙️ Step 3: Perform Conversion
         * -----------------------------------------------------------
         * Depending on the selected mode (--decode / --encode),
         * either converts TOON → JSON or JSON → TOON using the
         * core Toon service registered in the container.
         */
        try {
            if ($decode) {
                // Convert TOON → JSON
                $decoded = app('toon')->decode($input);
                $out = $pretty
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : json_encode($decoded);
            } else {
                // Convert JSON → TOON
                $out = app('toon')->convert($input);
            }
        } catch (ToonException $e) {
            $this->error('TOON parsing/serialization error: ' . $e->getMessage());
            return 3;
        } catch (\Throwable $e) {
            $this->error('Unexpected error: ' . $e->getMessage());
            return 4;
        }

        /**
         * -----------------------------------------------------------
         * 💾 Step 4: Output Result
         * -----------------------------------------------------------
         * Writes the converted data to:
         *   - A specified output file (via --output)
         *   - Or prints directly to the terminal
         */
        if ($output) {
            try {
                $fs->put($output, $out);
                $this->info("✅ Conversion complete! Saved to: {$output}");
            } catch (\Throwable $e) {
                $this->error("Failed to write output file: " . $e->getMessage());
                return 5;
            }
        } else {
            $this->line($out);
        }

        /**
         * -----------------------------------------------------------
         * ✅ Step 5: Exit Cleanly
         * -----------------------------------------------------------
         * Return zero to indicate successful command execution.
         */
        return 0;
    }
}
