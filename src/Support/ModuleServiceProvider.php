<?php

namespace Laraneat\Modules\Support;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Get publishable view paths
     */
    protected function getPublishableViewPaths(string $modulePackageName): array
    {
        $paths = [];

        foreach (config('view.paths', []) as $path) {
            if (is_dir($path . '/modules/' . $modulePackageName)) {
                $paths[] = $path . '/modules/' . $modulePackageName;
            }
        }

        return $paths;
    }

    /**
     * Register all commands in the given directory(ies).
     *
     * @throws ReflectionException
     */
    protected function loadCommandsFrom(
        array|string $paths,
        string $basePath,
        string $baseNamespace
    ): void
    {
        $paths = array_unique(Arr::wrap($paths));

        $paths = array_filter($paths, static function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        foreach (Finder::create()->in($paths)->files() as $file) {
            $command = $this->commandClassFromFile($file, $basePath, $baseNamespace);

            if (
                is_subclass_of($command, Command::class) &&
                ! (new ReflectionClass($command))->isAbstract()
            ) {
                Artisan::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
    }

    /**
     * Extract the command class name from the given file path.
     */
    protected function commandClassFromFile(
        SplFileInfo $file,
        string $basePath,
        string $baseNamespace,
    ): string
    {
        return rtrim($baseNamespace, '\\') . '\\' . str_replace(
            ['/', '.php'],
            ['\\', ''],
            Str::after($file->getRealPath(), realpath($basePath) . DIRECTORY_SEPARATOR)
        );
    }

    /**
     * Load files from directory
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
