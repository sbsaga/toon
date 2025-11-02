<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Illuminate\Support\ServiceProvider;
use Sbsaga\Toon\Converters\ToonConverter;

/**
 * Class ToonServiceProvider
 *
 * This service provider integrates the TOON library into a Laravel application.
 * It handles configuration publishing, command registration, and service binding
 * into the Laravel IoC (Inversion of Control) container.
 *
 * Responsibilities:
 * - Publish the package configuration file (`config/toon.php`).
 * - Register console commands for TOON conversions.
 * - Bind singleton instances for the TOON converter and main facade service.
 *
 * This class ensures that the TOON components are properly registered,
 * available via dependency injection, and usable through the `Toon` facade.
 *
 * Example usage:
 * ```php
 * use Sbsaga\Toon\Facades\Toon;
 *
 * $encoded = Toon::encode(['user' => 'Sagar']);
 * $decoded = Toon::decode($encoded);
 * ```
 *
 * @package Sbsaga\Toon
 * @author Sagar
 */
class ToonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * This method runs automatically when the service provider is booted.
     * It publishes configuration files and registers Artisan console commands.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish the configuration file to the Laravel application's config directory.
        $this->publishes([
            __DIR__ . '/../config/toon.php' => $this->app->configPath('toon.php'),
        ], 'config');

        // Register Artisan console commands if the application is running in console mode.
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ToonConvertCommand::class,
            ]);
        }
    }

    /**
     * Register package services and bindings in the service container.
     *
     * This method defines how the TOON services and converters should be
     * instantiated and made available throughout the Laravel application.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge the default package configuration with the application’s config.
        $this->mergeConfigFrom(__DIR__ . '/../config/toon.php', 'toon');

        // Register the ToonConverter as a singleton for optimized reuse.
        $this->app->singleton('toon.converter', function ($app) {
            return new ToonConverter($app->make('config')->get('toon', []));
        });

        // Register the main Toon service as a singleton, accessible via the Toon facade.
        $this->app->singleton('toon', function ($app) {
            return new Toon($app->make('toon.converter'));
        });
    }
}
