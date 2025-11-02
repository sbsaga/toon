<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Toon
 *
 * The Toon facade provides a simple and expressive static interface
 * for interacting with the TOON data conversion service.
 *
 * This class allows developers to access TOON functionality directly
 * via static methods such as:
 *
 * Example:
 * ```php
 * use Sbsaga\Toon\Facades\Toon;
 *
 * // Convert array to TOON format
 * $toonString = Toon::convert($data);
 *
 * // Decode TOON string to PHP array
 * $array = Toon::decode($toonString);
 * ```
 *
 * Behind the scenes, this facade resolves the underlying 'toon' binding
 * from the Laravel service container, ensuring clean dependency management
 * and testability.
 *
 * The actual implementation is handled by the TOON manager class,
 * which encapsulates the business logic for encoding and decoding.
 *
 * @package Sbsaga\Toon\Facades
 * @see \Sbsaga\Toon\ToonManager
 * @see \Sbsaga\Toon\Converters\ToonConverter
 * @author Sagar
 */
class Toon extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * This method tells the Laravel Facade system which key
     * should be used to resolve the underlying service from
     * the IoC (Inversion of Control) container.
     *
     * In this case, it resolves the 'toon' binding that represents
     * the core TOON service responsible for data transformations.
     *
     * @return string The container binding name.
     */
    protected static function getFacadeAccessor()
    {
        // The container binding name for the TOON service.
        // This must match the registration in your service provider.
        return 'toon';
    }
}
