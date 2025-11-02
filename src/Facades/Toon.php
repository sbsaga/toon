<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Toon
 *
 * @package Sbsaga\Toon\Facades
 * @author Sagar
 *
 * --------------------------------------------------------------------------
 * TOON Facade for Laravel
 * --------------------------------------------------------------------------
 *
 * This Facade provides a simple, expressive static interface to the core TOON
 * service within any Laravel application.
 *
 * It allows developers like Tannu, Mannu, or Surekha to easily encode or decode
 * data to and from TOON format without directly resolving the service container.
 *
 * ## Example Usage
 * ```php
 * use Sbsaga\Toon\Facades\Toon;
 *
 * // Convert an array to compact TOON format
 * $toon = Toon::convert([
 *     'user' => 'Sunil',
 *     'role' => 'admin',
 * ]);
 *
 * // Decode a TOON string back into a PHP array
 * $data = Toon::decode("user: Sunil\nrole: admin");
 *
 * // Pretty print or analyze in Laravel Tinker
 * dd($data);
 * ```
 *
 * ## Purpose
 * - Simplifies the use of the underlying TOON service by offering Laravel-style syntax.
 * - Keeps controllers, console commands, and middleware clean and expressive.
 * - Encourages consistent usage of TOON conversion across all app layers.
 *
 * ## Internal Behavior
 * - This facade resolves the `'toon'` binding from the Laravel service container.
 * - That binding is registered by the `ToonServiceProvider`.
 *
 * ## Example in a Controller
 * ```php
 * public function showPrompt()
 * {
 *     // Vikas uses TOON to minimize tokens before sending a request to an LLM.
 *     $payload = ['question' => 'What is AI?', 'user' => 'Vitthal'];
 *     $compact = Toon::convert($payload);
 *
 *     // Store or send optimized data
 *     return response()->json(['toon' => $compact]);
 * }
 * ```
 *
 * ## Design Note
 * - This class intentionally contains no business logic.
 * - Its only job is to provide an elegant, static entry point to the TOON service.
 * - The underlying instance is managed by Laravel’s IoC container for testability.
 *
 * --------------------------------------------------------------------------
 * @see \Sbsaga\Toon\ToonServiceProvider
 * --------------------------------------------------------------------------
 */
class Toon extends Facade
{
    /**
     * Get the registered name of the component from the service container.
     *
     * This method informs Laravel’s Facade system which binding to resolve
     * when a static method is called on this facade.
     *
     * For example:
     * ```php
     * Toon::convert($data);
     * ```
     * is equivalent to:
     * ```php
     * app('toon')->convert($data);
     * ```
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        // The service container binding name defined in ToonServiceProvider.
        return 'toon';
    }
}
