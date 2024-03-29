<?php

namespace Laraneat\Modules\Tests;

use Illuminate\Foundation\Application;
use Laraneat\Modules\ModulesServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class BaseTestCase extends OrchestraTestCase
{
    protected function getBasePath(): string
    {
        return __DIR__ . '/fixtures/laravel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutMockingConsoleOutput')) {
            $this->withoutMockingConsoleOutput();
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            ModulesServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('modules.paths.assets', public_path('modules'));
        $app['config']->set('modules.generator', [
            'path' => base_path('app/Modules'),
            'namespace' => 'App\\Modules',
            'custom_stubs' => base_path('app/Ship/Generators/custom-stubs'),
            'user_model' => 'App\\Modules\\User\\Models\\User',
            'create_permission' => [
                'action' => "App\\Modules\\Authorization\\Actions\\CreatePermissionAction",
                'dto' => "App\\Modules\\Authorization\\DTO\\CreatePermissionDTO"
            ],
            'components' => [
                'action' => [
                    'path' => 'Actions',
                    'generate' => true
                ],
                'api-controller' => [
                    'path' => 'UI/API/Controllers',
                    'generate' => false
                ],
                'api-query-wizard' => [
                    'path' => 'UI/API/QueryWizards',
                    'generate' => true
                ],
                'api-request' => [
                    'path' => 'UI/API/Requests',
                    'generate' => true
                ],
                'api-resource' => [
                    'path' => 'UI/API/Resources',
                    'generate' => true
                ],
                'api-route' => [
                    'path' => 'UI/API/Routes',
                    'generate' => true
                ],
                'api-test' => [
                    'path' => 'UI/API/Tests',
                    'generate' => true
                ],
                'cli-command' => [
                    'path' => 'UI/CLI/Commands',
                    'generate' => false
                ],
                'cli-test' => [
                    'path' => 'UI/CLI/Tests',
                    'generate' => false
                ],
                'dto' => [
                    'path' => 'DTO',
                    'generate' => true
                ],
                'event' => [
                    'path' => 'Events',
                    'generate' => false
                ],
                'exception' => [
                    'path' => 'Exceptions',
                    'generate' => false
                ],
                'factory' => [
                    'path' => 'Data/Factories',
                    'generate' => true
                ],
                'feature-test' => [
                    'path' => 'Tests/Feature',
                    'generate' => false
                ],
                'job' => [
                    'path' => 'Jobs',
                    'generate' => false
                ],
                'lang' => [
                    'path' => 'Resources/lang',
                    'generate' => false
                ],
                'listener' => [
                    'path' => 'Listeners',
                    'generate' => false
                ],
                'mail' => [
                    'path' => 'Mails',
                    'generate' => false
                ],
                'middleware' => [
                    'path' => 'Middleware',
                    'generate' => false
                ],
                'migration' => [
                    'path' => 'Data/Migrations',
                    'generate' => true
                ],
                'model' => [
                    'path' => 'Models',
                    'generate' => true
                ],
                'notification' => [
                    'path' => 'Notifications',
                    'generate' => false
                ],
                'observer' => [
                    'path' => 'Observers',
                    'generate' => false
                ],
                'policy' => [
                    'path' => 'Policies',
                    'generate' => true
                ],
                'provider' => [
                    'path' => 'Providers',
                    'generate' => true
                ],
                'rule' => [
                    'path' => 'Rules',
                    'generate' => false
                ],
                'seeder' => [
                    'path' => 'Data/Seeders',
                    'generate' => true
                ],
                'web-controller' => [
                    'path' => 'UI/WEB/Controllers',
                    'generate' => false
                ],
                'web-request' => [
                    'path' => 'UI/WEB/Requests',
                    'generate' => false,
                ],
                'web-route' => [
                    'path' => 'UI/WEB/Routes',
                    'generate' => false
                ],
                'web-test' => [
                    'path' => 'UI/WEB/Tests',
                    'generate' => false
                ],
                'view' => [
                    'path' => 'Resources/views',
                    'generate' => false
                ],
                'unit-test' => [
                    'path' => 'Tests/Unit',
                    'generate' => false
                ],
            ],
        ]);

        $app['config']->set('modules.cache.enabled', true);
        $app['config']->set('modules.composer.composer-output', true);
    }
}
