<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Sbsaga\Toon\Exceptions\ToonException;

/**
 * ==========================================================================
 *  TOON Console Command
 * ==========================================================================
 *
 * This class defines the `toon:convert` Artisan command for Laravel.
 *
 * It provides developers with the ability to convert between:
 *  - JSON / PHP arrays → TOON (encoding)
 *  - TOON → JSON / PHP arrays (decoding)
 *
 * The command is fully integrated into the Laravel console layer and supports:
 *   • CLI options (encode/decode modes)
 *   • STDIN / file-based I/O
 *   • Custom configuration loading
 *   • Pretty printing for decoded JSON
 *
 * --------------------------------------------------------------------------
 * Why use this command?
 * --------------------------------------------------------------------------
 * TOON (Token-Optimized Object Notation) is ideal when working with AI prompts
 * or LLM context data — it compresses structured information into a compact,
 * readable format, reducing token usage by up to 70%.
 *
 * Example real-world usage:
 *  • Tannu compresses a large prompt file before sending to OpenAI.
 *    $ php artisan toon:convert storage/prompt.json --encode
 *
 *  • Mannu decodes a TOON file for debugging model inputs.
 *    $ php artisan toon:convert storage/logs/input.toon --decode --pretty
 *
 * Author:  Sagar S. Bhedodkar
 * License: MIT
 * Package: sbsaga/toon
 */
class ToonConvertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Each argument and option is documented inline for easy reference:
     *
     * file         → Optional path to input file (defaults to STDIN)
     * --decode     → Convert TOON → JSON (default mode)
     * --encode     → Convert JSON/PHP → TOON
     * --output     → Save output to a file instead of stdout
     * --pretty     → Beautify JSON output (useful when decoding)
     * --config     → Provide custom configuration file (PHP returning array)
     *
     * This level of flexibility ensures developers can automate conversions
     * in pipelines, crons, or debugging sessions effortlessly.
     */
    protected $signature = 'toon:convert
        {file? : Path to input file; if omitted reads STDIN}
        {--d|--decode : Decode TOON to JSON (default behavior when --decode set)}
        {--e|--encode : Encode JSON/PHP to TOON}
        {--o|--output= : Output file (if omitted prints to stdout)}
        {--p|--pretty : Pretty-print JSON when decoding}
        {--c|--config= : Optional path to a custom toon config file (php returning array)}';

    /**
     * Command description displayed in `php artisan list` or help screen.
     */
    protected $description = 'Encode (or decode) a file/string to/from TOON format.';

    /**
     * Execute the command.
     *
     * This is the main entry point for the CLI interaction.
     * It handles argument parsing, configuration merging, and file I/O.
     *
     * @param Filesystem $fs  Laravel's filesystem abstraction.
     * @return int  Exit code (0 success, non-zero = failure)
     */
    public function handle(Filesystem $fs): int
    {
        // ------------------------------------------------------------------
        // STEP 1: Retrieve command-line arguments and options
        // ------------------------------------------------------------------
        // The developer can specify encode/decode modes, output path, etc.
        // Default behavior: decode TOON → JSON if no explicit --encode flag is provided.
        $file = $this->argument('file');
        $decode = $this->option('decode') || !$this->option('encode'); // defaults to decode mode
        $output = $this->option('output');
        $pretty = (bool)$this->option('pretty');
        $configPath = $this->option('config');

        // ------------------------------------------------------------------
        // STEP 2: Load optional custom configuration file
        // ------------------------------------------------------------------
        // Developers (like Sunil or Surekha) may maintain multiple environment-
        // specific configurations. This block dynamically includes and merges
        // them with Laravel's global 'toon' config at runtime.
        if ($configPath) {
            if (!$fs->exists($configPath)) {
                // Early exit if config file path invalid
                $this->error("Config file not found: {$configPath}");
                return 2;
            }
            try {
                /** @noinspection PhpIncludeInspection */
                $custom = include $configPath;

                // Ensure valid PHP array structure
                if (is_array($custom)) {
                    // Merge the new config with existing Laravel config('toon')
                    if (function_exists('config')) {
                        config(['toon' => array_merge(config('toon', []), $custom)]);
                    }
                }
            } catch (\Throwable $e) {
                // Handle invalid or malformed configuration includes gracefully
                $this->error("Failed to load config: " . $e->getMessage());
                return 2;
            }
        }

        // ------------------------------------------------------------------
        // STEP 3: Acquire input data from file or STDIN
        // ------------------------------------------------------------------
        // When the developer specifies a filename, it reads from disk.
        // Otherwise, it listens to STDIN for streamed input (useful in pipes).
        $input = null;
        try {
            if ($file) {
                if (!$fs->exists($file)) {
                    // Abort if path invalid or file missing
                    $this->error("File not found: {$file}");
                    return 1;
                }

                // Load file contents into memory
                $input = $fs->get($file);
            } else {
                // No file argument supplied → switch to interactive STDIN read
                $this->info('Reading from STDIN (press Ctrl+D or send EOF to end):');
                $input = stream_get_contents(STDIN);

                // Safety fallback in case of empty input stream
                if ($input === false) {
                    $input = '';
                }
            }
        } catch (\Throwable $e) {
            // Catch-all for file permission errors, missing directories, etc.
            $this->error("Failed to read input: " . $e->getMessage());
            return 1;
        }

        // ------------------------------------------------------------------
        // STEP 4: Perform Conversion (Encode or Decode)
        // ------------------------------------------------------------------
        // This block performs the actual conversion by leveraging the
        // TOON service registered within Laravel’s IoC container.
        //
        // It intelligently detects whether to encode or decode based on flags.
        // The service methods (`convert`, `decode`) are responsible for
        // the heavy lifting — compacting JSON or restoring it.
        //
        // Example usage:
        //   • Vikas encodes a 10KB JSON prompt for an LLM
        //     php artisan toon:convert data.json --encode
        //
        //   • Vitthal decodes the TOON file back into JSON for debugging
        //     php artisan toon:convert data.toon --decode --pretty
        try {
            if ($decode) {
                // --- Decoding Mode: TOON → JSON ---
                $decoded = app('toon')->decode($input);

                // Optionally pretty-print the resulting JSON for human readability
                $out = $pretty
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : json_encode($decoded);
            } else {
                // --- Encoding Mode: JSON/PHP → TOON ---
                // Here, the TOON service will intelligently interpret JSON
                // or array structures and compress them to the human-readable
                // TOON syntax. Escaping rules and thresholds are driven by config.
                $out = app('toon')->convert($input);
            }
        } catch (ToonException $e) {
            // Custom library exception — raised for invalid TOON syntax or parse errors
            $this->error('TOON parsing/serialization error: ' . $e->getMessage());
            return 3;
        } catch (\Throwable $e) {
            // Generic fallback for runtime issues (e.g., malformed JSON)
            $this->error('Unexpected error: ' . $e->getMessage());
            return 4;
        }

        // ------------------------------------------------------------------
        // STEP 5: Handle Output — Write to File or Display
        // ------------------------------------------------------------------
        // The final step determines whether to store results or print directly.
        // It provides flexibility for both automated workflows and
        // quick developer inspection in the terminal.
        if ($output) {
            try {
                // Write converted output to file using Laravel Filesystem
                $fs->put($output, $out);

                // Inform the developer about the saved file path
                $this->info("Saved to {$output}");
            } catch (\Throwable $e) {
                // Graceful error message if writing fails (e.g., permissions)
                $this->error("Failed to write output file: " . $e->getMessage());
                return 5;
            }
        } else {
            // If no output flag was specified, print result directly to console
            // This is particularly useful when piping into another command.
            $this->line($out);
        }

        // ------------------------------------------------------------------
        // STEP 6: Return success
        // ------------------------------------------------------------------
        // Exit code 0 denotes success in CLI environments.
        // Example automation:
        //   if (system('php artisan toon:convert input.json --encode') === 0) {
        //       echo "Conversion successful! - says Surekha\n";
        //   }
        return 0;
    }
}
