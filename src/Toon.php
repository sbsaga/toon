<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

/**
 * Core TOON service class.
 * Provides methods to encode arrays/objects to TOON
 * and decode TOON strings back to PHP arrays.
 */
class Toon
{
    protected ToonConverter $converter;

    protected ToonDecoder $decoder;

    /**
     * Constructor.
     *
     * @param ToonConverter $converter
     * @param ToonDecoder|null $decoder
     */
    public function __construct(ToonConverter $converter, ?ToonDecoder $decoder = null)
    {
        $this->converter = $converter;

        $this->decoder = $decoder ?? new ToonDecoder([
            'coerce_scalar_types' => $this->getConfig('coerce_scalar_types', true),
            'escape_style' => $this->getConfig('escape_style', 'backslash'),
        ]);
    }

    /**
     * Convert any input (array, object, JSON string) to TOON.
     *
     * @param mixed $input
     * @return string
     */
    public function convert(mixed $input): string
    {
        return $this->converter->toToon($input);
    }

    /**
     * Encode input to TOON (alias for convert).
     *
     * @param mixed $input
     * @return string
     */
    public function encode(mixed $input): string
    {
        return $this->convert($input);
    }

    /**
     * Decode TOON string back to PHP array.
     *
     * @param string $toon
     * @return array
     */
    public function decode(string $toon): array
    {
        return $this->decoder->fromToon($toon);
    }

    /**
     * Estimate tokens, words, and characters in a TOON string.
     *
     * @param string $toon
     * @return array
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
     * Get package config or default value.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null): mixed
    {
        if (function_exists('config')) {
            return config("toon.{$key}", $default);
        }

        return $default;
    }
}
