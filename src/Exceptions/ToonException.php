<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Exceptions;

use RuntimeException;

/**
 * Exception type used for TOON-specific parsing and serialization failures.
 *
 * ```php
 * use Sbsaga\Toon\Exceptions\ToonException;
 *
 * try {
 *     $data = app('toon')->decode($toon);
 * } catch (ToonException $e) {
 *     report($e);
 * }
 * ```
 */
class ToonException extends RuntimeException
{
}
