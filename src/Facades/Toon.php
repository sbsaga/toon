<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string convert(mixed $input) Convert array/object/JSON to TOON format.
 * @method static string encode(mixed $input) Alias for convert.
 * @method static array decode(string $toon) Convert TOON string to PHP array.
 * @method static array estimateTokens(string $toon) Estimate tokens/words/chars for TOON string.
 * 
 * @see \Sbsaga\Toon\Toon
 */
class Toon extends Facade
{
    /**
     * Get the registered name of the component in the container.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'toon';
    }
}
