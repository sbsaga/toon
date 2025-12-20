<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Illuminate\Support\ServiceProvider;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

class ToonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__ . '/../config/toon.php' => config_path('toon.php'),
        ], 'config');
    }

    /**
     * Register the package services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge default config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/toon.php',
            'toon'
        );

        // Bind ToonConverter as singleton
        $this->app->singleton(ToonConverter::class, function ($app) {
            $config = $app['config']->get('toon', []);
            return new ToonConverter($config);
        });

        // Bind ToonDecoder as singleton
        $this->app->singleton(ToonDecoder::class, function ($app) {
            $config = $app['config']->get('toon', []);
            return new ToonDecoder($config);
        });

        // Bind core Toon service as singleton
        $this->app->singleton(Toon::class, function ($app) {
            $converter = $app->make(ToonConverter::class);
            $decoder = $app->make(ToonDecoder::class);
            return new Toon($converter, $decoder);
        });

        // Register Facade alias automatically
        if (class_exists('Illuminate\Foundation\AliasLoader')) {
            \Illuminate\Foundation\AliasLoader::getInstance()->alias('Toon', Facades\Toon::class);
        }
    }
}
