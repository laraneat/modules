<?php

namespace Laraneat\Modules\Providers;

use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'modules');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/config.php' => config_path('modules.php'),
            ], 'config');
        }
    }
}
