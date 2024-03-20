<?php

namespace Laraneat\Modules\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Commands;

class ConsoleServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The available commands
     *
     * @var array<int, class-string>
     */
    protected array $commands = [
        Commands\CacheClearCommand::class,
        Commands\CacheCommand::class,
        Commands\ListCommand::class,
        Commands\MigrateCommand::class,
        Commands\MigrateRefreshCommand::class,
        Commands\MigrateResetCommand::class,
        Commands\MigrateRollbackCommand::class,
        Commands\MigrateStatusCommand::class,
        Commands\ModuleDeleteCommand::class,
        Commands\StubPublishCommand::class,
        Commands\SyncCommand::class,
        Commands\Generators\ActionMakeCommand::class,
        Commands\Generators\CommandMakeCommand::class,
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
        Commands\Generators\QueryWizardMakeCommand::class,
        Commands\Generators\RequestMakeCommand::class,
        Commands\Generators\ResourceMakeCommand::class,
        Commands\Generators\RouteMakeCommand::class,
        Commands\Generators\RuleMakeCommand::class,
        Commands\Generators\SeederMakeCommand::class,
        Commands\Generators\TestMakeCommand::class,
    ];

    /**
     * Register commands
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(config('modules.commands', $this->commands));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return $this->commands;
    }
}
