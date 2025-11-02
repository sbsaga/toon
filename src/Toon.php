<?php

namespace Sbsaga\Toon;

use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

class Toon
{
    protected ToonConverter $converter;
    protected ToonDecoder $decoder;

    public function __construct(ToonConverter $converter)
    {
        $this->converter = $converter;
        $this->decoder = new ToonDecoder();
    }

    /**
     * Convert PHP value (array/object/string) to TOON format string.
     */
    public function convert(mixed $input): string
    {
        return $this->converter->toToon($input);
    }

    /**
     * Decode a TOON formatted string back to PHP array.
     */
    public function decode(string $toon): array
    {
        return $this->decoder->fromToon($toon);
    }

    /**
     * Estimate tokens (simple heuristic).
     * This returns words and an estimated token count (approx).
     */
    public function estimateTokens(string $toon): array
    {
        $words = preg_split('/\s+/', trim($toon)) ?: [];
        $chars = strlen($toon);
        // rough heuristic: token ~ 4 chars (approx in many models), but use words * 0.75 as earlier plus char factor
        $tokenEstimate = max(1, (int) ceil(count($words) * 0.75 + $chars / 50));

        return [
            'words' => count($words),
            'chars' => $chars,
            'tokens_estimate' => $tokenEstimate,
        ];
    }
}
