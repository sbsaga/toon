<?php

namespace sbsaga\Toon;

use Sbsagar\Toon\Converters\ToonConverter;

class Toon
{
    protected ToonConverter $converter;

    public function __construct(ToonConverter $converter)
    {
        $this->converter = $converter;
    }

    public function convert(mixed $input): string
    {
        return $this->converter->toToon($input);
    }

    public function estimateTokens(string $toon): array
    {
        $words = preg_split('/\s+/', trim($toon));
        $tokenEstimate = max(1, (int) ceil(count($words) * 0.75));

        return [
            'words' => count($words),
            'tokens_estimate' => $tokenEstimate,
        ];
    }
}
