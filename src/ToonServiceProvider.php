<?php
declare(strict_types=1);

namespace Sbsaga\Toon;

use Illuminate\Support\ServiceProvider;
use Sbsaga\Toon\Converters\ToonConverter;

class ToonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/toon.php' => $this->app->configPath('toon.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ToonConvertCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/toon.php', 'toon');

        $this->app->singleton('toon.converter', function ($app) {
            return new ToonConverter($app->make('config')->get('toon', []));
        });

        $this->app->singleton('toon', function ($app) {
            return new Toon($app->make('toon.converter'));
        });
    }
}
