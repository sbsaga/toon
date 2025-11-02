<?php

namespace Sbsaga\Toon;

use Illuminate\Support\ServiceProvider;
use Sbsaga\Toon\Converters\ToonConverter;

class ToonServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/toon.php' => config_path('toon.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ToonConvertCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/toon.php', 'toon');

        $this->app->singleton('toon.converter', function ($app) {
            return new ToonConverter(config('toon', []));
        });

        $this->app->singleton('toon', function ($app) {
            return new Toon($app->make('toon.converter'));
        });

        // Optional facade alias already declared via composer extra for Laravel auto discovery
    }
}
