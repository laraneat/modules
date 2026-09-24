<?php

declare(strict_types=1);

namespace Laraneat\Modules\Tests;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\Scaffold\ApplicationComposer;
use Laraneat\Modules\Scaffold\ComposerJson;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Every test runs in its own copy of tests/Fixtures/app, with no real processes allowed.
 */
abstract class TestCase extends Orchestra
{
    /**
     * Providers registered before the package provider, like module providers of packages
     * whose names sort before "laraneat/modules".
     *
     * @var list<class-string>
     */
    protected array $providersBeforeModules = [];

    protected string $basePath;

    private Closure $autoloader;

    private string $workingDirectory;

    private ?int $composerRuns = null;

    /**
     * @var list<string>
     */
    private array $temporaryDirectories = [];

    protected function setUp(): void
    {
        $this->basePath = TemporaryDirectory::create(__DIR__.'/Fixtures/app');
        spl_autoload_register($this->autoloader = $this->autoloadModules(...));
        // Relative paths (and mutants that make paths relative) never reach the repository.
        $this->workingDirectory = (string) getcwd();
        chdir($this->basePath);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        if ($this->composerRuns !== null) {
            $this->assertSame(1, $this->composerRuns, 'Composer was expected to run once.');
        }

        parent::tearDown();

        spl_autoload_unregister($this->autoloader);
        chdir($this->workingDirectory);

        unset($_ENV['APP_RUNNING_IN_CONSOLE'], $_SERVER['APP_RUNNING_IN_CONSOLE']);
        ModuleRepository::flushState();
        // Static, so a package provider of one test would add its optimize task to the next ones.
        ServiceProvider::$optimizeCommands = ServiceProvider::$optimizeClearCommands = [];
        array_map(TemporaryDirectory::delete(...), [$this->basePath, ...$this->temporaryDirectories]);
    }

    /**
     * A directory outside of the application, removed after the test.
     */
    protected function temporaryDirectory(?string $copyOf = null): string
    {
        return $this->temporaryDirectories[] = TemporaryDirectory::create($copyOf);
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        Process::fake([])->preventStrayProcesses();
    }

    protected function getApplicationBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * The provider and the alias come from composer.json, as Laravel package discovery reads them.
     */
    protected function getPackageProviders($app): array
    {
        return [...$this->providersBeforeModules, ...self::composerJson()['extra']['laravel']['providers']];
    }

    protected function getPackageAliases($app): array
    {
        return self::composerJson()['extra']['laravel']['aliases'];
    }

    /**
     * Boot a fresh application, e.g. after the modules changed on disk.
     */
    protected function reboot(): void
    {
        ModuleRepository::flushState();

        $this->reloadApplication();
    }

    /**
     * Boot a fresh application that does not run in the console, like an HTTP worker.
     */
    protected function rebootForHttp(): void
    {
        $_ENV['APP_RUNNING_IN_CONSOLE'] = $_SERVER['APP_RUNNING_IN_CONSOLE'] = 'false';

        $this->reboot();
    }

    /**
     * Expect exactly one Composer run with the given arguments, in the application directory.
     *
     * @param  list<string>  $arguments
     * @param  (Closure(): void)|null  $during  Runs as Composer would, e.g. to check the filesystem.
     */
    protected function expectComposer(array $arguments, int $exitCode = 0, ?Closure $during = null): void
    {
        $this->composerRuns = 0;

        Process::fake(['*' => function (PendingProcess $process) use ($arguments, $exitCode, $during) {
            $this->composerRuns++;

            expect($process->command)->toBe(['composer', ...$arguments])
                ->and($process->path)->toBe($this->basePath)
                ->and($process->environment)->toBe(['COMPOSER_MEMORY_LIMIT' => '-1'])
                ->and($process->timeout)->toBeNull();

            if ($during !== null) {
                $during();
            }

            return Process::result(exitCode: $exitCode);
        }]);
    }

    /**
     * Wire the fixture modules into the application as "composer update" would: required,
     * installed (vendor/composer/installed.json) and linked from vendor.
     *
     * @param  list<string>  $modules
     */
    protected function installModules(array $modules = ['blog', 'shop-order']): void
    {
        $root = ComposerJson::read($this->path('composer.json'));
        $composer = new ApplicationComposer($this->basePath);
        $packages = [];

        foreach ($modules as $name) {
            $module = new Module(
                $name,
                'app/'.$name,
                'Modules\\'.str_replace(' ', '', ucwords(str_replace('-', ' ', $name))),
                $this->path('modules/'.$name),
                $this->path('modules/'.$name.'/src'),
            );
            $composer->addModule($root, $module, $this->path('modules'));

            $packages[] = [
                ...$this->readJson('modules/'.$name.'/composer.json'),
                'version' => 'dev-main',
                'dist' => ['type' => 'path', 'url' => 'modules/'.$name, 'reference' => null],
                'install-path' => '../app/'.$name,
            ];

            (new Filesystem)->ensureDirectoryExists($this->path('vendor/app'));
            // Composer links path repositories with a relative symlink, or with an absolute junction on Windows.
            symlink(DIRECTORY_SEPARATOR === '\\' ? $this->path('modules/'.$name) : '../../modules/'.$name, $this->path('vendor/app/'.$name));
        }

        $root->save();
        $this->files(['vendor/composer/installed.json' => json_encode(['packages' => $packages, 'dev' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)]);
    }

    /**
     * @param  array<string, string>  $files  Contents by path relative to the application.
     */
    protected function files(array $files): void
    {
        $filesystem = new Filesystem;

        foreach ($files as $path => $contents) {
            $filesystem->ensureDirectoryExists(dirname($this->basePath.'/'.$path));
            $filesystem->put($this->basePath.'/'.$path, $contents);
        }
    }

    protected function path(string $path = ''): string
    {
        return $this->basePath.($path === '' ? '' : '/'.$path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function readJson(string $path): array
    {
        return json_decode((string) file_get_contents($this->path($path)), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Classes of modules created by a test: Composer only knows the fixture modules.
     */
    private function autoloadModules(string $class): void
    {
        foreach (glob($this->basePath.'/*/*/composer.json') ?: [] as $composerJson) {
            $composer = json_decode((string) file_get_contents($composerJson), true);
            $psr4 = [...(array) ($composer['autoload']['psr-4'] ?? []), ...(array) ($composer['autoload-dev']['psr-4'] ?? [])];

            foreach ($psr4 as $namespace => $directories) {
                foreach ((array) $directories as $directory) {
                    $file = dirname($composerJson).'/'.$directory.'/'.str_replace('\\', '/', substr($class, strlen($namespace))).'.php';

                    if (str_starts_with($class, $namespace) && is_file($file)) {
                        require $file;

                        return;
                    }
                }
            }
        }
    }

    /**
     * @return array{extra: array{laravel: array{providers: list<class-string>, aliases: array<string, class-string>}}}
     */
    private static function composerJson(): array
    {
        return json_decode((string) file_get_contents(__DIR__.'/../composer.json'), true, 512, JSON_THROW_ON_ERROR);
    }
}
