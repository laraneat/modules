<?php

namespace Laraneat\Modules\Tests;

use Laraneat\Modules\Commands;
use Laraneat\Modules\LaravelModulesServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class BaseTestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutMockingConsoleOutput')) {
            $this->withoutMockingConsoleOutput();
        }
        // $this->setUpDatabase();
    }

    private function resetDatabase()
    {
        $this->artisan('migrate:reset', [
            '--database' => 'sqlite',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelModulesServiceProvider::class,
        ];
    }

    /**
     * Set up the environment.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('modules.namespace', 'App\Modules');
        $app['config']->set('modules.paths', [
            'modules' => base_path('app/Modules'),
            'assets' => public_path('modules'),
            'migration' => base_path('database/migrations'),
            'generator' => [
                'assets' => ['path' => 'Assets', 'generate' => true],
                'config' => ['path' => 'Config', 'generate' => true],
                'command' => ['path' => 'Console', 'generate' => true],
                'event' => ['path' => 'Events', 'generate' => true],
                'listener' => ['path' => 'Listeners', 'generate' => true],
                'migration' => ['path' => 'Database/Migrations', 'generate' => true],
                'factory' => ['path' => 'Database/factories', 'generate' => true],
                'model' => ['path' => 'Entities', 'generate' => true],
                'repository' => ['path' => 'Repositories', 'generate' => true],
                'seeder' => ['path' => 'Database/Seeders', 'generate' => true],
                'controller' => ['path' => 'Http/Controllers', 'generate' => true],
                'filter' => ['path' => 'Http/Middleware', 'generate' => true],
                'request' => ['path' => 'Http/Requests', 'generate' => true],
                'provider' => ['path' => 'Providers', 'generate' => true],
                'lang' => ['path' => 'Resources/lang', 'generate' => true],
                'views' => ['path' => 'Resources/views', 'generate' => true],
                'policies' => ['path' => 'Policies', 'generate' => true],
                'rules' => ['path' => 'Rules', 'generate' => true],
                'test-feature' => ['path' => 'Tests/Feature', 'generate' => true],
                'test' => ['path' => 'Tests/Unit', 'generate' => true],
                'jobs' => ['path' => 'Jobs', 'generate' => true],
                'emails' => ['path' => 'Emails', 'generate' => true],
                'notifications' => ['path' => 'Notifications', 'generate' => true],
                'resource' => ['path' => 'Transformers', 'generate' => true],
                'component-view' => ['path' => 'Resources/views/components', 'generate' => true],
                'component-class' => ['path' => 'View/Component', 'generate' => true],
            ],
        ]);

        $app['config']->set('modules.composer-output', true);

        $app['config']->set('modules.commands', [
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
            Commands\PublishCommand::class,
            Commands\PublishConfigurationCommand::class,
            Commands\PublishMigrationCommand::class,
            Commands\PublishTranslationCommand::class,
            Commands\SeedCommand::class,
            Commands\SetupCommand::class,
            Commands\UnUseCommand::class,
            Commands\UpdateCommand::class,
            Commands\UseCommand::class,
            Commands\Generators\CommandMakeCommand::class,
            Commands\Generators\ComponentClassMakeCommand::class,
            Commands\Generators\ComponentViewMakeCommand::class,
            Commands\Generators\ControllerMakeCommand::class,
            Commands\Generators\EventMakeCommand::class,
            Commands\Generators\FactoryMakeCommand::class,
            Commands\Generators\JobMakeCommand::class,
            Commands\Generators\ListenerMakeCommand::class,
            Commands\Generators\MailMakeCommand::class,
            Commands\Generators\MiddlewareMakeCommand::class,
            Commands\Generators\MigrationMakeCommand::class,
            Commands\Generators\ModelMakeCommand::class,
            Commands\Generators\ModuleMakeCommand::class,
            Commands\Generators\NotificationMakeCommand::class,
            Commands\Generators\PolicyMakeCommand::class,
            Commands\Generators\ProviderMakeCommand::class,
            Commands\Generators\RequestMakeCommand::class,
            Commands\Generators\ResourceMakeCommand::class,
            Commands\Generators\RouteProviderMakeCommand::class,
            Commands\Generators\RuleMakeCommand::class,
            Commands\Generators\SeedMakeCommand::class,
            Commands\Generators\TestMakeCommand::class,
        ]);
    }

    protected function setUpDatabase()
    {
        $this->resetDatabase();
    }
}
