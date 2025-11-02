<?php

namespace Sbsaga\Toon;

use Illuminate\Support\ServiceProvider;

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

        $this->app->singleton('toon', function ($app) {
            return new Toon(new \Sbsagar\Toon\Converters\ToonConverter(config('toon')));
        });
    }
}
