<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

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
}
