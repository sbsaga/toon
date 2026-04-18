<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;
use Sbsaga\Toon\Internal\ToonSkipValue;

/**
 * Public service for TOON encoding, decoding, and token estimation.
 *
 * ```php
 * use Sbsaga\Toon\Facades\Toon;
 *
 * $toon = Toon::encode(['user' => 'Alice', 'role' => 'admin']);
 * $decoded = Toon::decode($toon);
 * $stats = Toon::estimateTokens($toon);
 * $diff = Toon::diff(['user' => 'Alice']);
 * $prompt = Toon::promptBlock(['user' => 'Alice']);
 * ```
 */
class Toon
{
    /** Encoder used to serialize PHP data into TOON. */
    protected ToonConverter $converter;

    /** Decoder used to parse TOON into PHP arrays. */
    protected ToonDecoder $decoder;

    /**
     * Create a new service instance.
     *
     * @param ToonConverter $converter Converter implementation used for encoding.
     * @param ToonDecoder|null $decoder Optional decoder instance. When omitted, a decoder is
     *     created with runtime configuration values.
     */
    public function __construct(ToonConverter $converter, ?ToonDecoder $decoder = null)
    {
        $this->converter = $converter;

        // Lazily instantiate a decoder so standalone usage still honors configured defaults.
        $this->decoder = $decoder ?? new ToonDecoder([
            'coerce_scalar_types' => $this->getConfig('coerce_scalar_types', true),
            'escape_style' => $this->getConfig('escape_style', 'backslash'),
            'delimiter' => $this->getConfig('delimiter', 'comma'),
            'strict_mode' => $this->getConfig('strict_mode', false),
            'compatibility_mode' => $this->getConfig('compatibility_mode', 'legacy'),
        ]);
    }

    /**
     * Encode JSON, arrays, objects, or scalar values into TOON.
     *
     * @param mixed $input Data to encode.
     * @return string Serialized TOON payload.
     */
    public function convert(mixed $input): string
    {
        return $this->converter->toToon($input);
    }

    /**
     * Encode data into TOON after applying an optional replacer transform.
     *
     * @param mixed $input Data to encode.
     * @param callable|null $replacer Optional callback with signature:
     *     fn(array $path, string|int|null $key, mixed $value): mixed
     */
    public function convertWith(mixed $input, ?callable $replacer = null): string
    {
        if ($replacer === null) {
            return $this->convert($input);
        }

        $transformed = $this->applyReplacer($input, $replacer);
        if ($transformed === ToonSkipValue::instance()) {
            return '';
        }

        return $this->convert($transformed);
    }

    /**
     * Alias of {@see convert()} for callers that prefer encode/decode terminology.
     *
     * @param mixed $input Data to encode.
     * @return string Serialized TOON payload.
     */
    public function encode(mixed $input): string
    {
        return $this->convert($input);
    }

    /**
     * Alias of {@see convertWith()} for callers that prefer encode/decode terminology.
     *
     * @param mixed $input Data to encode.
     * @param callable|null $replacer Optional callback with signature:
     *     fn(array $path, string|int|null $key, mixed $value): mixed
     */
    public function encodeWith(mixed $input, ?callable $replacer = null): string
    {
        return $this->convertWith($input, $replacer);
    }

    /**
     * Decode a TOON payload into PHP arrays.
     *
     * @param string $toon Serialized TOON payload.
     * @return array Decoded PHP representation.
     */
    public function decode(string $toon): array
    {
        return $this->decoder->fromToon($toon);
    }

    /**
     * Yield encoded TOON line-by-line to support lightweight streaming workflows.
     *
     * @param mixed $input Data to encode.
     * @return \Generator<int,string>
     */
    public function encodeLines(mixed $input): \Generator
    {
        $encoded = $this->encode($input);
        if ($encoded === '') {
            return;
        }

        foreach (explode("\n", $encoded) as $line) {
            yield $line;
        }
    }

    /**
     * Decode TOON content from an iterable list of lines.
     *
     * @param iterable<mixed> $lines TOON lines.
     * @return array Decoded PHP representation.
     */
    public function decodeFromLines(iterable $lines): array
    {
        $buffer = [];
        foreach ($lines as $line) {
            $buffer[] = rtrim((string) $line, "\r\n");
        }

        return $this->decode(implode("\n", $buffer));
    }

    /**
     * Sentinel value for replacer callbacks to indicate that a value should be skipped.
     */
    public static function skip(): object
    {
        return ToonSkipValue::instance();
    }

    /**
     * Estimate token usage for a TOON payload using the package's original lightweight heuristic.
     *
     * @param string $toon Serialized TOON payload.
     * @return array{words:int,chars:int,tokens_estimate:int} Estimated counts for quick comparison.
     */
    public function estimateTokens(string $toon): array
    {
        // Preserve the package's original dependency-free heuristic for backward compatibility.
        $words = preg_split('/\s+/', trim($toon)) ?: [];
        $chars = strlen($toon);
        $tokenEstimate = max(1, (int) ceil(count($words) * 0.75 + $chars / 50));

        return [
            'words' => count($words),
            'chars' => $chars,
            'tokens_estimate' => $tokenEstimate,
        ];
    }

    /**
     * Compare JSON and TOON size/token characteristics for a payload.
     *
     * @return array{
     *     json_chars:int,
     *     toon_chars:int,
     *     saved_chars:int,
     *     savings_percent:float,
     *     json_tokens_estimate:int,
     *     toon_tokens_estimate:int,
     *     saved_tokens_estimate:int
     * }
     */
    public function diff(mixed $input): array
    {
        $json = $this->toJsonString($input);
        $toon = $this->convert($input);

        $jsonTokenEstimate = $this->estimateComparableTokens($json);
        $toonTokenEstimate = $this->estimateComparableTokens($toon);
        $jsonChars = strlen($json);
        $toonChars = strlen($toon);
        $savedChars = max(0, $jsonChars - $toonChars);

        return [
            'json_chars' => $jsonChars,
            'toon_chars' => $toonChars,
            'saved_chars' => $savedChars,
            'savings_percent' => $jsonChars > 0 ? round(($savedChars / $jsonChars) * 100, 2) : 0.0,
            'json_tokens_estimate' => $jsonTokenEstimate,
            'toon_tokens_estimate' => $toonTokenEstimate,
            'saved_tokens_estimate' => max(0, $jsonTokenEstimate - $toonTokenEstimate),
        ];
    }

    /**
     * Wrap encoded TOON in a fenced code block for LLM prompts and docs.
     *
     * @param mixed $input Data to encode.
     * @param string $fenceLabel Fence label to use in the markdown block.
     * @return string Markdown code block containing encoded TOON.
     */
    public function promptBlock(mixed $input, string $fenceLabel = 'toon'): string
    {
        $label = trim($fenceLabel) !== '' ? trim($fenceLabel) : 'toon';

        return "```{$label}\n" . $this->convert($input) . "\n```";
    }

    /**
     * Validate a TOON payload without throwing an exception to the caller.
     *
     * @param string $toon Serialized TOON payload.
     * @param bool $strict Whether strict table validation should be enabled.
     * @return array{valid:bool,error:?string}
     */
    public function validate(string $toon, bool $strict = true): array
    {
        try {
            $decoder = new ToonDecoder([
                'coerce_scalar_types' => $this->getConfig('coerce_scalar_types', true),
                'escape_style' => $this->getConfig('escape_style', 'backslash'),
                'delimiter' => $this->getConfig('delimiter', 'comma'),
                'strict_mode' => $strict,
                'compatibility_mode' => $this->getConfig('compatibility_mode', 'legacy'),
            ]);

            $decoder->fromToon($toon);

            return [
                'valid' => true,
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'valid' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Return the conventional TOON content type for HTTP and file responses.
     */
    public function contentType(): string
    {
        return 'text/toon; charset=utf-8';
    }

    /**
     * Return the conventional file extension for TOON documents.
     */
    public function fileExtension(): string
    {
        return 'toon';
    }

    /**
     * Resolve a configuration value when Laravel's config helper is available.
     *
     * @param string $key Configuration key without the `toon.` prefix.
     * @param mixed $default Fallback value for non-Laravel environments.
     * @return mixed The resolved configuration value.
     */
    protected function getConfig(string $key, $default = null)
    {
        if (function_exists('config')) {
            return config("toon.{$key}", $default);
        }
        return $default;
    }

    /**
     * Normalize input as JSON for comparison metrics.
     */
    protected function toJsonString(mixed $input): string
    {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return (string) json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            return (string) json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (is_object($input)) {
            $input = json_decode(json_encode($input), true);
        }

        return (string) json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Estimate comparable token counts for JSON-vs-TOON reporting.
     */
    protected function estimateComparableTokens(string $payload): int
    {
        return max(1, (int) ceil(strlen($payload) / 4));
    }

    /**
     * Apply a replacer callback recursively to all values in the input tree.
     *
     * @param callable $replacer fn(array $path, string|int|null $key, mixed $value): mixed
     * @return mixed
     */
    protected function applyReplacer(mixed $input, callable $replacer): mixed
    {
        return $this->walkWithReplacer($input, [], null, $replacer);
    }

    /**
     * Walk nested values and apply a replacer callback.
     *
     * @param mixed $value Current value.
     * @param array<int,string|int> $path Path to current value.
     * @param string|int|null $key Current key.
     * @param callable $replacer fn(array $path, string|int|null $key, mixed $value): mixed
     * @return mixed
     */
    protected function walkWithReplacer(mixed $value, array $path, string|int|null $key, callable $replacer): mixed
    {
        $replaced = $replacer($path, $key, $value);
        if ($replaced === ToonSkipValue::instance()) {
            return ToonSkipValue::instance();
        }

        $iterable = $this->normalizeIterable($replaced);
        if ($iterable === null) {
            return $replaced;
        }

        $output = [];
        foreach ($iterable as $childKey => $childValue) {
            $nextPath = [...$path, is_int($childKey) ? $childKey : (string) $childKey];
            $nextValue = $this->walkWithReplacer($childValue, $nextPath, $childKey, $replacer);
            if ($nextValue === ToonSkipValue::instance()) {
                continue;
            }

            $output[$childKey] = $nextValue;
        }

        return $output;
    }

    /**
     * Convert supported iterables to arrays for replacer traversal.
     *
     * @return array<mixed>|null
     */
    protected function normalizeIterable(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof \Traversable) {
            return iterator_to_array($value);
        }

        if (!is_object($value)) {
            return null;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return null;
        }

        $decoded = json_decode($encoded, true);

        return is_array($decoded) ? $decoded : null;
    }
}
