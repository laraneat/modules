<?php

namespace Laraneat\Modules;

use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Exceptions\InvalidActivatorClass;
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
        $this->app->register(ConsoleServiceProvider::class);
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
        $this->app->register(BootstrapServiceProvider::class);
    }

    protected function registerServices(): void
    {
        $this->app->alias(RepositoryInterface::class, 'modules');
        $this->app->singleton(RepositoryInterface::class, function ($app) {
            return new FileRepository($app);
        });

        $this->app->singleton(ActivatorInterface::class, function ($app) {
            $activator = $app['config']->get('modules.activator');
            $class = $app['config']->get('modules.activators.' . $activator)['class'];

            if ($class === null) {
                throw InvalidActivatorClass::missingConfig();
            }

            return new $class($app);
        });
    }
}
