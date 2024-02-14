<?php

namespace Laraneat\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Providers\BootstrapServiceProvider;
use Laraneat\Modules\Providers\ConsoleServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'modules');
        $this->registerServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('modules.php'),
            ], 'config');
        }
    }

    protected function registerServices(): void
    {
        $this->app->alias(ModulesRepository::class, 'modules');
        $this->app->singleton(ModulesRepository::class, function (Application $app) {
            /** @phpstan-ignore-next-line  */
            return new ModulesRepository($app);
        });

        $this->app->register(ConsoleServiceProvider::class);
        $this->app->register(BootstrapServiceProvider::class);
    }
}
