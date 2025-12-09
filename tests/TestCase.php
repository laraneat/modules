<?php

namespace Laraneat\Modules\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModulesRepository;
use Laraneat\Modules\Providers\ComposerServiceProvider;
use Laraneat\Modules\Providers\ConsoleServiceProvider;
use Laraneat\Modules\Providers\ModulesRepositoryServiceProvider;
use Laraneat\Modules\Providers\ModulesServiceProvider;
use Laraneat\Modules\Support\Composer;
use Laraneat\Modules\Support\Facades\Modules;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Mockery;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public Filesystem $filesystem;

    /** @var string[] */
    protected array $modulesPaths = [];

    protected ?string $composerJsonBackupPath = null;

    public static function applicationBasePath(): string
    {
        return __DIR__ . '/fixtures/laravel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->app['files'];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ComposerServiceProvider::class,
            ConsoleServiceProvider::class,
            ModulesRepositoryServiceProvider::class,
            ModulesServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Modules' => Modules::class,
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
    }

    /**
     * @param string[] $paths
     */
    public function setModules(array $paths, ?string $modulesPath = null): void
    {
        $modulesPath = rtrim($modulesPath ?? GeneratorHelper::getBasePath(), '/');
        $this->addModulesPath($modulesPath);
        $this->filesystem->ensureDirectoryExists($modulesPath);

        foreach ($paths as $modulePath) {
            $modulePath = rtrim($modulePath, '/');
            $this->filesystem->copyDirectory($modulePath, $modulesPath . '/' . Str::afterLast($modulePath, '/'));
        }

        $this->app[ModulesRepository::class]->pruneModulesManifest();
    }

    public function pruneModulesPaths(): void
    {
        if ($this->modulesPaths) {
            foreach ($this->modulesPaths as $path) {
                if ($this->filesystem->isDirectory($path)) {
                    $this->filesystem->deleteDirectory($path);
                }
            }
            $this->modulesPaths = [];
            $this->app[ModulesRepository::class]->pruneModulesManifest();
        }
    }

    public function backupComposerJson(): void
    {
        if ($this->composerJsonBackupPath !== null) {
            return;
        }

        $this->composerJsonBackupPath = $this->app->basePath('/composer.backup.json');
        if ($this->filesystem->isFile($this->composerJsonBackupPath)) {
            $this->resetComposerJson();
            $this->composerJsonBackupPath = $this->app->basePath('/composer.backup.json');
        }
        $this->filesystem->copy($this->app->basePath('/composer.json'), $this->composerJsonBackupPath);
    }

    public function resetComposerJson(): void
    {
        if ($this->composerJsonBackupPath === null) {
            return;
        }

        if ($this->filesystem->isFile($this->composerJsonBackupPath)) {
            $this->filesystem->delete($this->app->basePath('/composer.json'));
            $this->filesystem->copy($this->composerJsonBackupPath, $this->app->basePath('/composer.json'));
            $this->filesystem->delete($this->composerJsonBackupPath);
        }
        $this->composerJsonBackupPath = null;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function createModule(array $attributes = []): Module
    {
        return new Module(
            packageName: $attributes['packageName'] ?? 'some-vendor/testing-module',
            name: $attributes['name'] ?? '',
            path: $attributes['path'] ?? $this->app->basePath('modules/TestingModule'),
            namespace: $attributes['namespace'] ?? 'SomeVendor\\TestingModule\\',
            providers: $attributes['providers'] ?? [
                'SomeVendor\\TestingModule\\Providers\\TestingModuleServiceProvider',
                'SomeVendor\\TestingModule\\Providers\\RouteServiceProvider',
            ],
            aliases: $attributes['aliases'] ?? [
                'testing-module' => 'SomeVendor\\TestingModule\\Facades\\TestingModule',
            ],
        );
    }

    /**
     * Create a mock of Composer class for testing.
     *
     * @param array<string, mixed> $methodExpectations Array of method names to their expected return values
     *
     * @return MockInterface&Composer
     */
    public function mockComposer(array $methodExpectations = []): MockInterface
    {
        $composer = Mockery::mock(Composer::class);

        foreach ($methodExpectations as $method => $returnValue) {
            $composer->shouldReceive($method)->andReturn($returnValue);
        }

        return $composer;
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
