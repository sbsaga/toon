<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Illuminate\Support\ServiceProvider;
use Sbsaga\Toon\Converters\ToonConverter;

/**
 * Registers package services, configuration, and console commands with Laravel.
 *
 * ```php
 * $toon = app('toon');
 * $encoded = $toon->encode(['id' => 1, 'name' => 'Alice']);
 * ```
 */
class ToonServiceProvider extends ServiceProvider
{
    /**
     * Publish configuration and register console commands.
     */
    public function boot(): void
    {
        // Publish the package configuration so applications can override defaults.
        $this->publishes([
            __DIR__ . '/../config/toon.php' => $this->app->configPath('toon.php'),
        ], 'config');

        // Avoid registering console-only commands during HTTP requests.
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ToonConvertCommand::class,
            ]);
        }
    }

    /**
     * Register the package bindings in the service container.
     */
    public function register(): void
    {
        // Merge package defaults before resolving any dependent services.
        $this->mergeConfigFrom(__DIR__ . '/../config/toon.php', 'toon');

        // Expose the low-level converter for consumers that only need encoding behavior.
        $this->app->singleton('toon.converter', function ($app) {
            return new ToonConverter($app->make('config')->get('toon', []));
        });

        // Register the primary service used by the facade and application container.
        $this->app->singleton('toon', function ($app) {
            return new Toon($app->make('toon.converter'));
        });
    }
}
