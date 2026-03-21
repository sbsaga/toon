<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for the TOON service.
 *
 * ```php
 * use Sbsaga\Toon\Facades\Toon;
 *
 * $toon = Toon::convert(['user' => 'Alice']);
 * $data = Toon::decode($toon);
 * ```
 *
 * @method static string convert(mixed $data) Encode data as TOON.
 * @method static string encode(mixed $data) Alias of convert().
 * @method static array decode(string $toonString) Decode TOON into PHP arrays.
 * @method static array{words:int,chars:int,tokens_estimate:int} estimateTokens(string $data) Estimate token usage.
 * @method static array{json_chars:int,toon_chars:int,saved_chars:int,savings_percent:float,json_tokens_estimate:int,toon_tokens_estimate:int,saved_tokens_estimate:int} diff(mixed $data) Compare JSON and TOON size/token savings.
 * @method static string promptBlock(mixed $data, string $fenceLabel = 'toon') Wrap TOON in a fenced markdown block.
 * @method static array{valid:bool,error:?string} validate(string $toonString, bool $strict = true) Validate TOON input without throwing.
 * @method static string contentType() Return the conventional TOON content type.
 * @method static string fileExtension() Return the conventional TOON file extension.
 */
class Toon extends Facade
{
    /**
     * Get the service container binding resolved by this facade.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'toon';
    }
}
