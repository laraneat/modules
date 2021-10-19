<?php

namespace Laraneat\Modules\Traits;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;

/**
 * @mixin \Illuminate\Support\ServiceProvider
 */
trait ModuleProviderHelpersTrait
{
    /**
     * Get publishable view paths
     *
     * @param string $moduleKey
     *
     * @return array
     */
    protected function getPublishableViewPaths(string $moduleKey): array
    {
        $paths = [];

        foreach (config('view.paths', []) as $path) {
            if (is_dir($path . '/modules/' . $moduleKey)) {
                $paths[] = $path . '/modules/' . $moduleKey;
            }
        }

        return $paths;
    }

    /**
     * Merge configurations from directory with the existing configuration.
     *
     * @param string $directory
     * @param string|null $namespace
     *
     * @return void
     */
    protected function loadConfigs(string $directory, ?string $namespace = null): void
    {
        if (!($this->app instanceof CachesConfiguration && $this->app->configurationIsCached()) && File::isDirectory($directory)) {
            $files = File::files($directory);
            $namespace = $namespace ? $namespace . '::' : '';

            foreach ($files as $file) {
                $config = File::getRequire($file);
                $name = File::name($file);

                // special case for files named config.php (config keyword is omitted)
                if ($name === 'config') {
                    foreach ($config as $key => $value) {
                        Config::set($namespace . $key, $value);
                    }
                }

                Config::set($namespace . $name, $config);
            }
        }
    }

    /**
     * Register all of the commands in the given directory.
     *
     * @param array|string $paths
     * @return void
     *
     * @throws ReflectionException
     */
    protected function loadCommands(array|string $paths): void
    {
        $paths = array_unique(Arr::wrap($paths));

        $paths = array_filter($paths, static function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        $namespace = $this->app->getNamespace();

        foreach ((new Finder)->in($paths)->files() as $command) {
            $command = $namespace . str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($command->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
            );

            if (is_subclass_of($command, Command::class) &&
                ! (new ReflectionClass($command))->isAbstract()) {
                Artisan::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
    }

    /**
     * Load files from directory
     *
     * @param string $directory
     *
     * @return void
     */
    protected function loadFiles(string $directory): void
    {
        if (File::isDirectory($directory)) {
            $files = File::files($directory);

            foreach ($files as $file) {
                require_once $file;
            }
        }
    }

    /**
     * Load all files from directory
     *
     * @param string $directory
     *
     * @return void
     */
    protected function loadAllFiles(string $directory): void
    {
        if (File::isDirectory($directory)) {
            $files = File::allFiles($directory);

            foreach ($files as $file) {
                require_once $file;
            }
        }
    }
}
