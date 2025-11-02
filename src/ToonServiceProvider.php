<?php

namespace Sbsaga\Toon;

use Illuminate\Support\ServiceProvider;
use Sbsaga\Toon\Converters\ToonConverter;

class ToonServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__ . '/../config/toon.php' => config_path('toon.php'),
        ], 'config');

        // Register artisan commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ToonConvertCommand::class,
            ]);
        }
    }

    public function register()
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/toon.php', 'toon');

        // Bind the main Toon service into the container
        $this->app->singleton('toon', function ($app) {
            return new Toon(new ToonConverter(config('toon')));
        });
    }
}
