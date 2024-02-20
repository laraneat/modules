<?php

namespace Laraneat\Modules\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModulesRepository;
use Laraneat\Modules\ModulesServiceProvider;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public Filesystem $filesystem;

    /** @var string[] $modulesPaths */
    protected array $modulesPaths = [];

    protected ?string $composerJsonBackupPath = null;

    protected function getBasePath(): string
    {
        return __DIR__ . '/fixtures/laravel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->app['files'];
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

        $app['config']->set('modules.paths.modules', [base_path('app/Modules')]);
        $app['config']->set('modules.generator', [
            'path' => base_path('app/Modules'),
            'namespace' => 'App\\Modules',
            'custom_stubs' => base_path('stubs/modules'),
            'user_model' => 'App\\Modules\\User\\Models\\User',
            'create_permission' => [
                'action' => "App\\Modules\\Authorization\\Actions\\CreatePermissionAction",
                'dto' => "App\\Modules\\Authorization\\DTO\\CreatePermissionDTO",
            ],
            'components' => [
                'action' => [
                    'path' => 'Actions',
                    'generate' => true,
                ],
                'api-controller' => [
                    'path' => 'UI/API/Controllers',
                    'generate' => false,
                ],
                'api-query-wizard' => [
                    'path' => 'UI/API/QueryWizards',
                    'generate' => true,
                ],
                'api-request' => [
                    'path' => 'UI/API/Requests',
                    'generate' => true,
                ],
                'api-resource' => [
                    'path' => 'UI/API/Resources',
                    'generate' => true,
                ],
                'api-route' => [
                    'path' => 'UI/API/Routes',
                    'generate' => true,
                ],
                'api-test' => [
                    'path' => 'UI/API/Tests',
                    'generate' => true,
                ],
                'cli-command' => [
                    'path' => 'UI/CLI/Commands',
                    'generate' => false,
                ],
                'cli-test' => [
                    'path' => 'UI/CLI/Tests',
                    'generate' => false,
                ],
                'dto' => [
                    'path' => 'DTO',
                    'generate' => true,
                ],
                'event' => [
                    'path' => 'Events',
                    'generate' => false,
                ],
                'exception' => [
                    'path' => 'Exceptions',
                    'generate' => false,
                ],
                'factory' => [
                    'path' => 'Data/Factories',
                    'generate' => true,
                ],
                'feature-test' => [
                    'path' => 'Tests/Feature',
                    'generate' => false,
                ],
                'job' => [
                    'path' => 'Jobs',
                    'generate' => false,
                ],
                'lang' => [
                    'path' => 'lang',
                    'generate' => false,
                ],
                'listener' => [
                    'path' => 'Listeners',
                    'generate' => false,
                ],
                'mail' => [
                    'path' => 'Mails',
                    'generate' => false,
                ],
                'middleware' => [
                    'path' => 'Middleware',
                    'generate' => false,
                ],
                'migration' => [
                    'path' => 'Data/Migrations',
                    'generate' => true,
                ],
                'model' => [
                    'path' => 'Models',
                    'generate' => true,
                ],
                'notification' => [
                    'path' => 'Notifications',
                    'generate' => false,
                ],
                'observer' => [
                    'path' => 'Observers',
                    'generate' => false,
                ],
                'policy' => [
                    'path' => 'Policies',
                    'generate' => true,
                ],
                'provider' => [
                    'path' => 'Providers',
                    'generate' => true,
                ],
                'rule' => [
                    'path' => 'Rules',
                    'generate' => false,
                ],
                'seeder' => [
                    'path' => 'Data/Seeders',
                    'generate' => true,
                ],
                'web-controller' => [
                    'path' => 'UI/WEB/Controllers',
                    'generate' => false,
                ],
                'web-request' => [
                    'path' => 'UI/WEB/Requests',
                    'generate' => false,
                ],
                'web-route' => [
                    'path' => 'UI/WEB/Routes',
                    'generate' => false,
                ],
                'web-test' => [
                    'path' => 'UI/WEB/Tests',
                    'generate' => false,
                ],
                'view' => [
                    'path' => 'resources/views',
                    'generate' => false,
                ],
                'unit-test' => [
                    'path' => 'Tests/Unit',
                    'generate' => false,
                ],
            ],
        ]);

        $app['config']->set('modules.cache.enabled', true);
        $app['config']->set('modules.composer.composer-output', true);
    }

    /**
     * @param string[] $paths
     */
    public function setAppModules(array $paths, ?string $appModulesPath = null): void
    {
        $appModulesPath = rtrim($appModulesPath ?? GeneratorHelper::getBasePath(), '/');
        $this->addModulesPath($appModulesPath);
        $this->filesystem->ensureDirectoryExists($appModulesPath);

        foreach($paths as $modulePath) {
            $modulePath = rtrim($modulePath, '/');
            $this->filesystem->copyDirectory($modulePath, $appModulesPath . '/' . Str::afterLast($modulePath, '/'));
        }
        $this->app['modules']->pruneAppModulesManifest();
    }

    /**
     * @param string[] $paths
     * @throws FileNotFoundException
     */
    public function setVendorModules(array $paths): void
    {
        $vendorPath = $this->app->basePath('/vendor');
        $this->addModulesPath($vendorPath);
        $this->filesystem->ensureDirectoryExists($vendorPath . '/composer');

        $packages = [];
        foreach ($paths as $modulePath) {
            $modulePath = rtrim($modulePath, '/');
            $composerJson = json_decode($this->filesystem->get($modulePath . '/composer.json'), true);

            $segments = explode('/', $modulePath);
            $packagePath = implode('/', array_slice($segments, -2, 2));
            if ($composerJson['name'] !== $packagePath) {
                $packagePath = Str::afterLast($modulePath, '/');
            }

            $this->filesystem->copyDirectory($modulePath, $vendorPath . '/' . $packagePath);
            $composerJson['install-path'] = '../' . $packagePath;
            $packages[] = $composerJson;
        }

        $this->filesystem->ensureDirectoryExists($vendorPath . '/composer');
        $this->filesystem->replace($vendorPath . '/composer/installed.json', json_encode([
            'packages' => $packages,
        ]));
        $this->app['modules']->pruneVendorModulesManifest();
    }

    public function pruneModulesPaths(): void
    {
        if ($this->modulesPaths) {
            foreach($this->modulesPaths as $path) {
                if ($this->filesystem->isDirectory($path)) {
                    $this->filesystem->deleteDirectory($path);
                }
            }
            $this->modulesPaths = [];
            $this->app['modules']->pruneModulesManifest();
        }
    }

    /**
     * @param string[] $dontDiscover
     * @return void
     */
    public function setLaraneatDontDiscover(array $dontDiscover): void
    {
        $composerJsonPath = $this->app->basePath('/composer.json');
        if ($this->composerJsonBackupPath === null) {
            $this->composerJsonBackupPath = $this->app->basePath('/composer.json.bak');
            $this->filesystem->copy($composerJsonPath, $this->composerJsonBackupPath);
        }

        $composerJson = json_decode($this->filesystem->get($composerJsonPath), true);
        $composerJson['extra']['laraneat']['dont-discover'] = $dontDiscover;

        $this->filesystem->put($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT));
    }

    public function resetComposerJson(): void
    {
        if ($this->composerJsonBackupPath === null) {
            return;
        }

        $this->filesystem->move($this->composerJsonBackupPath, $this->app->basePath('/composer.json'));
        $this->composerJsonBackupPath = null;
    }

    public function createModule(array $attributes = []): Module
    {
        return new Module(
            app: $this->app,
            modulesRepository: $this->app[ModulesRepository::class],
            isVendor: $attributes['isVendor'] ?? false,
            packageName: $attributes['packageName'] ?? 'some-vendor/testing-module',
            name: $attributes['name'] ?? null,
            path: $attributes['path'] ?? $this->app->basePath('app/Modules/TestingModule'),
            namespace: $attributes['namespace'] ?? 'SomeVendor\\TestingModule\\',
            providers: $attributes['providers'] ?? [
                'SomeVendor\\TestingModule\\Providers\\TestingModuleServiceProvider',
                'SomeVendor\\TestingModule\\Providers\\RouteServiceProvider'
            ],
            aliases: $attributes['aliases'] ?? [
                'testing-module' => 'SomeVendor\\TestingModule\\Facades\\TestingModule',
            ],
        );
    }

    private function addModulesPath(string $path): void
    {
        if (in_array($path, $this->modulesPaths, true)) {
            return;
        }

        if ($this->filesystem->isDirectory($path)) {
            $this->filesystem->deleteDirectories($path);
        }

        $this->modulesPaths[] = $path;
    }
}
