<?php

namespace Laraneat\Modules;

use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Exceptions\InvalidActivatorClass;
use Laraneat\Modules\Providers\BootstrapServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! app()->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'modules');
        }

        $this->app->register(BootstrapServiceProvider::class);
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

            $this->loadCommands();
        }
    }

    protected function registerServices(): void
    {
        $this->app->alias(RepositoryInterface::class, 'modules');
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
    }

    public function loadCommands(): void
    {
        $this->commands([
            Commands\CacheClearCommand::class,
            Commands\CacheCommand::class,
            Commands\DisableCommand::class,
            Commands\DumpCommand::class,
            Commands\EnableCommand::class,
            Commands\InstallCommand::class,
            Commands\ListCommand::class,
            Commands\MigrateCommand::class,
            Commands\MigrateRefreshCommand::class,
            Commands\MigrateResetCommand::class,
            Commands\MigrateRollbackCommand::class,
            Commands\MigrateStatusCommand::class,
            Commands\ModuleDeleteCommand::class,
            Commands\SeedCommand::class,
            Commands\SetupCommand::class,
            Commands\UnUseCommand::class,
            Commands\UpdateCommand::class,
            Commands\UseCommand::class,
            Commands\Generators\ActionMakeCommand::class,
            Commands\Generators\CommandMakeCommand::class,
            Commands\Generators\ComponentsMakeCommand::class,
            Commands\Generators\ControllerMakeCommand::class,
            Commands\Generators\DTOMakeCommand::class,
            Commands\Generators\EventMakeCommand::class,
            Commands\Generators\ExceptionMakeCommand::class,
            Commands\Generators\FactoryMakeCommand::class,
            Commands\Generators\JobMakeCommand::class,
            Commands\Generators\ListenerMakeCommand::class,
            Commands\Generators\MailMakeCommand::class,
            Commands\Generators\MiddlewareMakeCommand::class,
            Commands\Generators\MigrationMakeCommand::class,
            Commands\Generators\ModelMakeCommand::class,
            Commands\Generators\ModuleMakeCommand::class,
            Commands\Generators\NotificationMakeCommand::class,
            Commands\Generators\ObserverMakeCommand::class,
            Commands\Generators\PolicyMakeCommand::class,
            Commands\Generators\ProviderMakeCommand::class,
            Commands\Generators\RouteMakeCommand::class,
            Commands\Generators\QueryWizardMakeCommand::class,
            Commands\Generators\RequestMakeCommand::class,
            Commands\Generators\ResourceMakeCommand::class,
            Commands\Generators\RuleMakeCommand::class,
            Commands\Generators\SeederMakeCommand::class,
            Commands\Generators\TestMakeCommand::class,
        ]);
    }
}
