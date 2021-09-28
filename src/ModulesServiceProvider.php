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
     * Booting the package.
     */
    public function boot(): void
    {
        $this->loadConfigs();
        $this->app->register(BootstrapServiceProvider::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerServices();
        $this->registerProviders();
    }

    protected function loadConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'modules');

        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('modules.php'),
        ], 'config');
    }

    /**
     * Register services.
     */
    protected function registerServices(): void
    {
        $this->app->singleton(RepositoryInterface::class, function ($app) {
            $path = $app['config']->get('modules.generator.path');

            return new FileRepository($app, $path);
        });

        $this->app->singleton(ActivatorInterface::class, function ($app) {
            $activator = $app['config']->get('modules.activator');
            $class = $app['config']->get('modules.activators.' . $activator)['class'];

            if ($class === null) {
                throw InvalidActivatorClass::missingConfig();
            }

            return new $class($app);
        });

        $this->app->alias(RepositoryInterface::class, 'modules');
    }

    /**
     * Register providers.
     */
    protected function registerProviders(): void
    {
        $this->app->register(ConsoleServiceProvider::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [RepositoryInterface::class, 'modules'];
    }
}
