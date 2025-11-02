<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

/**
 * Class Toon
 *
 * @package Sbsaga\Toon
 * @author Sagar
 *
 * --------------------------------------------------------------------------
 * The Core TOON Service
 * --------------------------------------------------------------------------
 *
 * This is the central class responsible for bridging conversion between
 * structured PHP/JSON data and the TOON (Token-Optimized Object Notation) format.
 *
 * It exposes simple public APIs:
 *  - `convert()` or `encode()` → for JSON/array → TOON conversion
 *  - `decode()` → for TOON → PHP/array conversion
 *  - `estimateTokens()` → lightweight heuristic token estimator
 *
 * The class acts as the glue between `ToonConverter` and `ToonDecoder`,
 * keeping configuration and operational logic consistent.
 *
 * --------------------------------------------------------------------------
 * ## Example: Basic Conversion Workflow
 *
 * ```php
 * use Sbsaga\Toon\Facades\Toon;
 *
 * // Mannu wants to minimize AI prompt tokens
 * $data = [
 *     'user' => 'Tannu',
 *     'task' => 'Summarize 5 reports',
 *     'priority' => 'high',
 * ];
 *
 * // Convert array to TOON format
 * $toon = Toon::convert($data);
 *
 * // Example output:
 * // user: Tannu
 * // task: Summarize 5 reports
 * // priority: high
 *
 * // Decode TOON back into array
 * $decoded = Toon::decode($toon);
 * ```
 *
 * --------------------------------------------------------------------------
 * ## Design Philosophy
 *
 * - **Minimal overhead**: Uses lightweight heuristics and native PHP.
 * - **Human-readable**: Keeps output legible for developers like Surekha and Sunil.
 * - **LLM-ready**: Optimized for prompt engineering and token efficiency.
 * - **Safe defaults**: Automatically detects JSON, arrays, or scalar types.
 *
 * --------------------------------------------------------------------------
 * ## Real-World Example (Laravel Controller)
 *
 * ```php
 * public function optimizePrompt()
 * {
 *     // Vikas uses TOON to preprocess LLM context
 *     $payload = [
 *         'question' => 'Explain reinforcement learning',
 *         'author'   => 'Vitthal'
 *     ];
 *
 *     $compact = Toon::convert($payload);
 *     $stats = Toon::estimateTokens($compact);
 *
 *     return response()->json([
 *         'toon' => $compact,
 *         'stats' => $stats,
 *     ]);
 * }
 * ```
 *
 * --------------------------------------------------------------------------
 */
class Toon
{
    /**
     * The internal converter that handles PHP → TOON conversion.
     *
     * @var ToonConverter
     */
    protected ToonConverter $converter;

    /**
     * The internal decoder that handles TOON → PHP conversion.
     *
     * @var ToonDecoder
     */
    protected ToonDecoder $decoder;

    /**
     * Create a new TOON service instance.
     *
     * This constructor accepts a `ToonConverter` and an optional `ToonDecoder`.
     * If no decoder is supplied, it automatically creates one using Laravel config.
     *
     * @param ToonConverter $converter
     * @param ToonDecoder|null $decoder
     */
    public function __construct(ToonConverter $converter, ?ToonDecoder $decoder = null)
    {
        $this->converter = $converter;

        // If decoder not provided, construct one dynamically with app config values.
        $this->decoder = $decoder ?? new ToonDecoder([
            'coerce_scalar_types' => $this->getConfig('coerce_scalar_types', true),
            'escape_style' => $this->getConfig('escape_style', 'backslash'),
        ]);
    }

    /**
     * Convert arbitrary input into TOON format.
     *
     * Accepts JSON strings, arrays, or objects.
     * Automatically handles indentation, escaping, and compact table rendering.
     *
     * @param mixed $input JSON, array, or object
     * @return string TOON representation
     */
    public function convert(mixed $input): string
    {
        return $this->converter->toToon($input);
    }

    /**
     * Alias for `convert()` to make intent explicit when encoding.
     *
     * This method is semantically identical but provides more clarity when
     * the developer explicitly wants to generate TOON.
     *
     * Example:
     * ```php
     * $toon = Toon::encode(['user' => 'Sunil', 'status' => 'active']);
     * ```
     *
     * @param mixed $input
     * @return string
     */
    public function encode(mixed $input): string
    {
        return $this->convert($input);
    }

    /**
     * Decode a TOON string into an associative PHP array.
     *
     * Handles nested blocks, tabular structures, escaped values,
     * and scalar coercion (e.g., "true" → true).
     *
     * @param string $toon
     * @return array
     */
    public function decode(string $toon): array
    {
        return $this->decoder->fromToon($toon);
    }

    /**
     * Estimate token usage of a given TOON string.
     *
     * This method uses a heuristic model — combining approximate word count
     * and character density — to estimate total token consumption.
     *
     * It helps developers like Surekha quickly gauge how compact their
     * converted data is compared to raw JSON.
     *
     * Example:
     * ```php
     * $stats = Toon::estimateTokens($compactToon);
     * // Returns: ['words' => 20, 'chars' => 180, 'tokens_estimate' => 22]
     * ```
     *
     * @param string $toon
     * @return array{words:int,chars:int,tokens_estimate:int}
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
     * Retrieve configuration values from Laravel's config system if available.
     *
     * This method gracefully degrades for non-Laravel environments (e.g., CLI or testing).
     * It ensures that default values like `escape_style` and `coerce_scalar_types`
     * remain consistent even outside Laravel.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        if (function_exists('config')) {
            return config("toon.{$key}", $default);
        }
        return $default;
    }
}
