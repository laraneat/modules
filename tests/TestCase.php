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
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Process\Process;

abstract class TestCase extends OrchestraTestCase
{
    public Filesystem $filesystem;

    /** @var string[] */
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

        foreach($paths as $modulePath) {
            $modulePath = rtrim($modulePath, '/');
            $this->filesystem->copyDirectory($modulePath, $modulesPath . '/' . Str::afterLast($modulePath, '/'));
        }

        $this->app[ModulesRepository::class]->pruneModulesManifest();
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

    public function createModule(array $attributes = []): Module
    {
        return new Module(
            app: $this->app,
            modulesRepository: $this->app[ModulesRepository::class],
            packageName: $attributes['packageName'] ?? 'some-vendor/testing-module',
            name: $attributes['name'] ?? null,
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
     * @return MockObject&Composer
     */
    public function mockComposer(array $expectedProcessArguments, bool $customComposerPhar = false, array $environmentVariables = []): MockObject
    {
        $directory = __DIR__;

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')->once()->with($directory.'/composer.phar')->andReturn($customComposerPhar);

        $process = Mockery::mock(Process::class);
        $process->shouldReceive('run')->once();

        $composer = $this->getMockBuilder(Composer::class)
            ->onlyMethods(['getProcess'])
            ->setConstructorArgs([$files, $directory, $environmentVariables])
            ->getMock();

        $composer->expects($this->once())
            ->method('getProcess')
            ->with($expectedProcessArguments)
            ->willReturn($process);

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
