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
