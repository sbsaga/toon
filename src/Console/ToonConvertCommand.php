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
        {--from=auto : Input format (auto|json|toon)}
        {--to=auto : Output format (auto|json|toon)}
        {--o|--output= : Output file (if omitted prints to stdout)}
        {--p|--pretty : Pretty-print JSON when decoding}
        {--stats : Print conversion metrics to STDERR as JSON}
        {--delimiter= : Override delimiter (comma|pipe|tab or raw character)}
        {--mode= : Compatibility mode override (legacy|modern)}
        {--strict : Enable strict table validation for decoding}
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
        $decodeOption = (bool) $this->option('decode');
        $encodeOption = (bool) $this->option('encode');
        $fromOption = $this->option('from');
        $toOption = $this->option('to');
        $output = $this->option('output');
        $pretty = (bool) $this->option('pretty');
        $stats = (bool) $this->option('stats');
        $delimiterOption = $this->option('delimiter');
        $modeOption = $this->option('mode');
        $strictOption = (bool) $this->option('strict');
        $configPath = $this->option('config');

        $direction = $this->resolveDirection(
            $file !== null ? (string) $file : null,
            $decodeOption,
            $encodeOption,
            $fromOption !== null ? (string) $fromOption : 'auto',
            $toOption !== null ? (string) $toOption : 'auto'
        );
        if ($direction === null) {
            $this->error('Invalid direction options. Use valid from/to pairs or explicit --encode/--decode.');
            return 2;
        }

        // Allow a one-off config override without changing the application's published config file.
        if ($configPath) {
            if (!$fs->exists($configPath)) {
                $this->error("Config file not found: {$configPath}");
                return 2;
            }

            try {
                $custom = include $configPath;

                if (is_array($custom)) {
                    $this->mergeToonConfig($custom);
                }
            } catch (\Throwable $e) {
                $this->error("Failed to load config: " . $e->getMessage());
                return 2;
            }
        }

        $runtimeOverrides = $this->buildRuntimeOverrides($modeOption, $delimiterOption, $strictOption);
        if ($runtimeOverrides === null) {
            return 2;
        }
        if ($runtimeOverrides !== []) {
            $this->mergeToonConfig($runtimeOverrides);
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
            if ($direction === 'decode') {
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

        if ($stats) {
            $this->writeStats($direction, $input, (string) $out);
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

    /**
     * Resolve encode/decode direction using explicit flags, from/to options, and file extension fallback.
     */
    protected function resolveDirection(
        ?string $file,
        bool $decodeOption,
        bool $encodeOption,
        string $fromOption,
        string $toOption
    ): ?string {
        if ($decodeOption) {
            return 'decode';
        }

        if ($encodeOption) {
            return 'encode';
        }

        $from = strtolower(trim($fromOption));
        $to = strtolower(trim($toOption));
        $validFormats = ['auto', 'json', 'toon'];
        if (!in_array($from, $validFormats, true) || !in_array($to, $validFormats, true)) {
            return null;
        }

        if ($from !== 'auto' || $to !== 'auto') {
            if ($from === 'json' && $to === 'json') {
                return null;
            }

            if ($from === 'toon' && $to === 'toon') {
                return null;
            }

            if ($from === 'json' || $to === 'toon') {
                if ($from === 'toon' || $to === 'json') {
                    return null;
                }

                return 'encode';
            }

            if ($from === 'toon' || $to === 'json') {
                return 'decode';
            }
        }

        if ($file !== null && $file !== '') {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($extension === 'json') {
                return 'encode';
            }

            if ($extension === 'toon') {
                return 'decode';
            }
        }

        // Preserve original command compatibility when no direction can be inferred.
        return 'decode';
    }

    /**
     * Build runtime config overrides from command-line options.
     *
     * @return array<string,mixed>|null
     */
    protected function buildRuntimeOverrides($modeOption, $delimiterOption, bool $strictOption): ?array
    {
        $overrides = [];

        if ($modeOption !== null && trim((string) $modeOption) !== '') {
            $mode = strtolower(trim((string) $modeOption));
            if (!in_array($mode, ['legacy', 'modern'], true)) {
                $this->error("Invalid --mode value '{$mode}'. Use legacy or modern.");
                return null;
            }

            $overrides['compatibility_mode'] = $mode;
        }

        if ($delimiterOption !== null && trim((string) $delimiterOption) !== '') {
            $overrides['delimiter'] = trim((string) $delimiterOption);
        }

        if ($strictOption) {
            $overrides['strict_mode'] = true;
        }

        return $overrides;
    }

    /**
     * Merge TOON config values when the Laravel config helper is available.
     *
     * @param array<string,mixed> $values
     */
    protected function mergeToonConfig(array $values): void
    {
        if (function_exists('config')) {
            config(['toon' => array_merge(config('toon', []), $values)]);
        }
    }

    /**
     * Write conversion stats to STDERR without affecting command stdout output.
     */
    protected function writeStats(string $direction, string $input, string $output): void
    {
        $stats = [
            'direction' => $direction,
            'input_chars' => strlen($input),
            'output_chars' => strlen($output),
        ];

        if ($direction === 'encode') {
            $stats['diff'] = app('toon')->diff($input);
        } else {
            $stats['tokens'] = app('toon')->estimateTokens($input);
        }

        $payload = json_encode($stats, JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return;
        }

        $this->getOutput()->getErrorStyle()->writeln($payload);
    }
}
