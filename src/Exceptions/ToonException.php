<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Exceptions;

use RuntimeException;

/**
 * Class ToonException
 *
 * @package Sbsaga\Toon\Exceptions
 * @author Sagar
 *
 * This custom exception class represents all errors or unexpected conditions that occur
 * during the encoding or decoding of TOON data.
 *
 * TOON (Token Optimized Object Notation) aims to be lightweight, readable, and
 * reversible — meaning any issues that break structural integrity or violate parsing rules
 * should immediately raise a `ToonException`.
 *
 * ## Why a Custom Exception?
 * Using a specific exception type like this allows developers such as Tannu, Mannu, or Surekha
 * to catch TOON-related errors separately from generic runtime errors.
 *
 * Example:
 * ```php
 * use Sbsaga\Toon\Converters\ToonDecoder;
 * use Sbsaga\Toon\Exceptions\ToonException;
 *
 * $decoder = new ToonDecoder();
 *
 * try {
 *     $data = $decoder->fromToon($toonString);
 * } catch (ToonException $e) {
 *     // Sunil can log and gracefully handle malformed TOON data.
 *     error_log('TOON decoding failed: ' . $e->getMessage());
 * }
 * ```
 *
 * ## Common Scenarios Where This Exception May Be Thrown
 * - When TOON syntax is malformed (e.g., unbalanced indentation or invalid table headers)
 * - When decoding fails due to missing keys or unexpected formats
 * - When Vikas or Vitthal mistakenly modify TOON config options leading to parse errors
 *
 * This class intentionally extends `RuntimeException` to preserve full compatibility
 * with Laravel and PHP’s native exception hierarchy, making it easy to integrate
 * with existing error handlers, loggers, and debug pipelines.
 *
 * ## Design Notes
 * - The class is intentionally empty because all behavior is inherited.
 * - If, in future, Tannu needs structured error codes (e.g., "E_TOON_SYNTAX"),
 *   they can be added here without affecting existing exception handling logic.
 *
 * @see \RuntimeException
 */
class ToonException extends RuntimeException
{
    // No additional functionality yet — serves as a marker for TOON-specific errors.
    // Keeping this class lightweight ensures zero overhead in production while maintaining
    // a clean separation of concern for error domains.
}
