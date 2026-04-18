<?php
declare(strict_types=1);

use Illuminate\Support\Collection;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Toon;

if (!function_exists('toon_resolver')) {
    /**
     * Resolve a TOON service instance from Laravel when available, otherwise build a standalone instance.
     */
    function toon_resolver(): Toon
    {
        if (function_exists('app')) {
            try {
                $app = app();
                if ($app !== null && method_exists($app, 'bound') && $app->bound('toon')) {
                    return $app->make('toon');
                }
            } catch (\Throwable) {
                // Fall back to a standalone instance when the helper is used outside Laravel bootstrapping.
            }
        }

        static $toon = null;

        return $toon ??= new Toon(new ToonConverter());
    }
}

if (!function_exists('toon_encode')) {
    /**
     * Encode data as TOON.
     */
    function toon_encode(mixed $data): string
    {
        return toon_resolver()->encode($data);
    }
}

if (!function_exists('toon_encode_with')) {
    /**
     * Encode data as TOON after applying a replacer callback.
     *
     * @param callable $replacer fn(array $path, string|int|null $key, mixed $value): mixed
     */
    function toon_encode_with(mixed $data, callable $replacer): string
    {
        return toon_resolver()->encodeWith($data, $replacer);
    }
}

if (!function_exists('toon_decode')) {
    /**
     * Decode a TOON payload into PHP arrays.
     */
    function toon_decode(string $toon): array
    {
        return toon_resolver()->decode($toon);
    }
}

if (!function_exists('toon_encode_lines')) {
    /**
     * Encode data as TOON and return the result as an array of lines.
     *
     * @return array<int,string>
     */
    function toon_encode_lines(mixed $data): array
    {
        return iterator_to_array(toon_resolver()->encodeLines($data), false);
    }
}

if (!function_exists('toon_diff')) {
    /**
     * Measure JSON-to-TOON size and token savings for a payload.
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
    function toon_diff(mixed $data): array
    {
        return toon_resolver()->diff($data);
    }
}

if (!function_exists('toon_prompt')) {
    /**
     * Wrap encoded TOON in a fenced markdown code block for prompt usage.
     */
    function toon_prompt(mixed $data, string $label = 'toon'): string
    {
        return toon_resolver()->promptBlock($data, $label);
    }
}

if (!function_exists('toon_validate')) {
    /**
     * Validate a TOON payload without throwing an exception.
     *
     * @return array{valid:bool,error:?string}
     */
    function toon_validate(string $toon, bool $strict = true): array
    {
        return toon_resolver()->validate($toon, $strict);
    }
}

if (class_exists(Collection::class) && method_exists(Collection::class, 'hasMacro') && !Collection::hasMacro('toToon')) {
    Collection::macro('toToon', function (): string {
        /** @var Collection $this */
        return toon_encode($this->all());
    });
}
