<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

class Toon
{
    protected ToonConverter $converter;
    protected ToonDecoder $decoder;

    public function __construct(ToonConverter $converter, ?ToonDecoder $decoder = null)
    {
        $this->converter = $converter;
        $this->decoder = $decoder ?? new ToonDecoder([
            'coerce_scalar_types' => $this->getConfig('coerce_scalar_types', true),
            'escape_style' => $this->getConfig('escape_style', 'backslash'),
        ]);
    }

    public function convert(mixed $input): string
    {
        return $this->converter->toToon($input);
    }

    /**
     * Alias for convert when you want to explicitly create TOON format from array/object.
     */
    public function encode(mixed $input): string
    {
        return $this->convert($input);
    }

    public function decode(string $toon): array
    {
        return $this->decoder->fromToon($toon);
    }

    /**
     * Estimate tokens (heuristic).
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
     * Helper to get config values when used inside Laravel (if available).
     */
    protected function getConfig(string $key, $default = null)
    {
        if (function_exists('config')) {
            return config("toon.{$key}", $default);
        }
        return $default;
    }
}
