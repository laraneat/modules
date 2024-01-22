<?php

namespace Laraneat\Modules\Traits;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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

        foreach (Finder::create()->in($paths)->files() as $file) {
            $command = $this->commandClassFromFile($file, $namespace);

            if (
                is_subclass_of($command, Command::class) &&
                !(new ReflectionClass($command))->isAbstract()
            ) {
                Artisan::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
    }

    /**
     * Extract the command class name from the given file path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $namespace
     * @return string
     */
    protected function commandClassFromFile(SplFileInfo $file, string $namespace): string
    {
        return $namespace . str_replace(
            ['/', '.php'],
            ['\\', ''],
            Str::after($file->getRealPath(), realpath(app_path()) . DIRECTORY_SEPARATOR)
        );
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
