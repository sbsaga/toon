<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

/**
 * Class Toon
 *
 * The central interface for encoding and decoding data using the TOON format.
 * This class acts as a high-level API that integrates the conversion and decoding
 * logic through dedicated converter and decoder components.
 *
 * Responsibilities:
 * - Convert arrays, objects, and JSON strings into TOON format.
 * - Decode TOON strings back into PHP arrays or objects.
 * - Optionally estimate token usage (useful for integrations with AI models).
 * - Pull runtime configuration from Laravel's config system if available.
 *
 * This class is typically resolved through the Laravel service container
 * and accessed via the `Toon` facade or dependency injection.
 *
 * Example:
 * ```php
 * use Sbsaga\Toon\Facades\Toon;
 *
 * $toonString = Toon::encode(['name' => 'Sagar', 'role' => 'Developer']);
 * $array = Toon::decode($toonString);
 * ```
 *
 * @package Sbsaga\Toon
 * @see \Sbsaga\Toon\Converters\ToonConverter
 * @see \Sbsaga\Toon\Converters\ToonDecoder
 * @author Sagar
 */
class Toon
{
    /**
     * The converter responsible for encoding data structures into TOON format.
     *
     * @var \Sbsaga\Toon\Converters\ToonConverter
     */
    protected ToonConverter $converter;

    /**
     * The decoder responsible for parsing TOON strings back into arrays.
     *
     * @var \Sbsaga\Toon\Converters\ToonDecoder
     */
    protected ToonDecoder $decoder;

    /**
     * Create a new Toon service instance.
     *
     * @param  \Sbsaga\Toon\Converters\ToonConverter  $converter
     * @param  \Sbsaga\Toon\Converters\ToonDecoder|null  $decoder
     */
    public function __construct(ToonConverter $converter, ?ToonDecoder $decoder = null)
    {
        $this->converter = $converter;

        // Initialize the decoder, using provided instance or default configuration.
        $this->decoder = $decoder ?? new ToonDecoder([
            'coerce_scalar_types' => $this->getConfig('coerce_scalar_types', true),
            'escape_style' => $this->getConfig('escape_style', 'backslash'),
        ]);
    }

    /**
     * Convert arbitrary input data into TOON format.
     *
     * This method can handle arrays, objects, JSON strings, or scalars.
     * It delegates the conversion process to the ToonConverter component.
     *
     * @param  mixed  $input  The input data to convert (array, object, or string).
     * @return string  The generated TOON string.
     */
    public function convert(mixed $input): string
    {
        return $this->converter->toToon($input);
    }

    /**
     * Encode data explicitly into TOON format.
     *
     * This is an alias of `convert()` for semantic clarity, allowing
     * developers to call `encode()` when explicitly generating TOON output.
     *
     * @param  mixed  $input  The input data to encode.
     * @return string  The TOON-encoded representation.
     */
    public function encode(mixed $input): string
    {
        return $this->convert($input);
    }

    /**
     * Decode a TOON-formatted string back into a PHP array.
     *
     * @param  string  $toon  The TOON string to decode.
     * @return array  The decoded associative array.
     */
    public function decode(string $toon): array
    {
        return $this->decoder->fromToon($toon);
    }

    /**
     * Estimate token usage for a given TOON string.
     *
     * This method provides an approximate count of "tokens" (words + structure),
     * which can be helpful for analyzing data complexity or estimating cost
     * in AI-driven systems that use token-based billing.
     *
     * Note: This is purely heuristic and not an exact tokenizer.
     *
     * @param  string  $toon  The TOON string to analyze.
     * @return array{
     *     words: int,
     *     chars: int,
     *     tokens_estimate: int
     * }  An associative array of word, character, and estimated token counts.
     */
    public function estimateTokens(string $toon): array
    {
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
     * Retrieve configuration values from the Laravel config system (if available).
     *
     * Falls back to a provided default if the `config()` helper is not defined
     * or if the key is missing. This design ensures the class remains portable
     * outside Laravel while still integrating seamlessly when used within it.
     *
     * @param  string  $key  The configuration key (relative to the 'toon' namespace).
     * @param  mixed   $default  Default value if the key is not found.
     * @return mixed  The resolved configuration value or the default.
     */
    protected function getConfig(string $key, $default = null)
    {
        if (function_exists('config')) {
            return config("toon.{$key}", $default);
        }

        return $default;
    }
}
