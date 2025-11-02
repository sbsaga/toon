<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Exceptions;

use RuntimeException;

/**
 * --------------------------------------------------------------------------
 * Class: ToonException
 * --------------------------------------------------------------------------
 *
 * Custom exception class for the **Sbsaga\Toon** package.
 * 
 * This exception acts as the unified error type for all TOON-related
 * runtime issues — such as invalid data structures, formatting errors,
 * or internal conversion failures.
 *
 * --------------------------------------------------------------------------
 * 🧩 Why Use a Custom Exception?
 * --------------------------------------------------------------------------
 * Having a domain-specific exception allows:
 *  - Clear separation of TOON-specific errors from other PHP exceptions.
 *  - Easier debugging and filtering (e.g., try/catch ToonException only).
 *  - Better extensibility if custom subtypes are added in future versions.
 * 
 * --------------------------------------------------------------------------
 * Example Usage:
 * --------------------------------------------------------------------------
 * ```php
 * use Sbsaga\Toon\Exceptions\ToonException;
 * 
 * throw new ToonException('Invalid TOON data encountered while parsing.');
 * ```
 * 
 * --------------------------------------------------------------------------
 * Author:  Sagar Bhedodkar
 * License: MIT
 * --------------------------------------------------------------------------
 */
class ToonException extends RuntimeException
{
    // 🧱 This class intentionally left minimal.
    // 
    // In the future, you could add:
    //  - Custom constructors (e.g., with error codes or context info)
    //  - Static factory methods (e.g., ToonException::invalidFormat())
    //  - Logging hooks or diagnostic tracing
    //
    // Keeping it simple ensures clean exception handling while maintaining
    // full compatibility with standard PHP RuntimeException behavior.
}
