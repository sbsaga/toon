<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Illuminate\Support\ServiceProvider;
use Sbsaga\Toon\Converters\ToonConverter;

/**
 * Class ToonServiceProvider
 *
 * @package Sbsaga\Toon
 * --------------------------------------------------------------------------
 * The TOON Service Provider for Laravel
 * --------------------------------------------------------------------------
 *
 * This provider registers all core components of the TOON framework:
 *
 * - Publishes the `config/toon.php` file for application-level customization.
 * - Registers the `toon` singleton in Laravel’s IoC container.
 * - Wires up the `ToonConverter`, `ToonDecoder`, and `Toon` facade binding.
 * - Registers artisan commands such as `toon:convert` for CLI use.
 *
 * This class ensures that any Laravel application can easily
 * encode/decode data using TOON format with zero setup.
 *
 * --------------------------------------------------------------------------
 * ## Example
 * After registering in `config/app.php` under providers:
 *
 * ```php
 * 'providers' => [
 *     // ...
 *     Sbsaga\Toon\ToonServiceProvider::class,
 * ],
 * ```
 *
 * You can now use:
 * ```php
 * use Sbsaga\Toon\Facades\Toon;
 *
 * $encoded = Toon::encode(['user' => 'Tannu', 'project' => 'AI Docs']);
 * $decoded = Toon::decode($encoded);
 * ```
 *
 * --------------------------------------------------------------------------
 * ## Notes
 * - Developers like Surekha or Sunil can easily customize the default
 *   configuration by publishing and editing `config/toon.php`.
 * - Works seamlessly in both console and web environments.
 * --------------------------------------------------------------------------
 */
class ToonServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * This method handles publishing assets (like configuration files)
     * and registering console commands when running in CLI mode.
     *
     * Example usage:
     * ```bash
     * php artisan vendor:publish --tag=config
     * php artisan toon:convert example.json --encode
     * ```
     */
    public function boot(): void
    {
        // Publish the package configuration to the application's config path
        $this->publishes([
            __DIR__ . '/../config/toon.php' => $this->app->configPath('toon.php'),
        ], 'config');

        // Register console commands only when the app is running in CLI context
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ToonConvertCommand::class,
            ]);
        }
    }

    /**
     * Register bindings in the service container.
     *
     * This ensures that the `toon` service and its dependencies
     * are available throughout the application lifecycle.
     *
     * The design separates the converter and main service into
     * distinct singletons for testability and modular usage.
     */
    public function register(): void
    {
        // Merge package configuration with application's own
        $this->mergeConfigFrom(__DIR__ . '/../config/toon.php', 'toon');

        /**
         * Bind the core converter service.
         *
         * Example:
         *   $converter = app('toon.converter');
         *   $toon = $converter->toToon(['author' => 'Mannu']);
         */
        $this->app->singleton('toon.converter', function ($app) {
            return new ToonConverter($app->make('config')->get('toon', []));
        });

        /**
         * Bind the main TOON service as a singleton.
         *
         * Example:
         *   $toon = app('toon');
         *   echo $toon->encode(['user' => 'Surekha']);
         */
        $this->app->singleton('toon', function ($app) {
            return new Toon($app->make('toon.converter'));
        });
    }
}
