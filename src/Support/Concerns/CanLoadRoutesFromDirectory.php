<?php

namespace Laraneat\Modules\Support\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @mixin \Illuminate\Foundation\Support\Providers\RouteServiceProvider
 */
trait CanLoadRoutesFromDirectory
{
    /**
     * Load routes from directory.
     *
     * @param string $directory
     * @param string $routePrefix
     * @param bool $generateRoutePrefixesByNestedDirectories
     *
     * @return void
     */
    protected function loadRoutesFromDirectory(
        string $directory,
        string $routePrefix = "",
        bool $generateRoutePrefixesByNestedDirectories = true
    ): void {
        if (! File::isDirectory($directory)) {
            return;
        }

        foreach (File::directories($directory) as $nestedDirectory) {
            $directoryRoutePrefix = $generateRoutePrefixesByNestedDirectories ? basename($nestedDirectory) : "";
            $this->loadRoutesFromDirectory(
                $nestedDirectory,
                $directoryRoutePrefix,
                $generateRoutePrefixesByNestedDirectories
            );
        }

        // Only "*.php" files are routes: leftovers like "*.php.orig" or "README.md" must never be required.
        /** @var SplFileInfo[] $files */
        $files = Arr::sort(
            array_filter(File::files($directory), static fn (SplFileInfo $file) => $file->getExtension() === 'php'),
            static fn (SplFileInfo $file) => $file->getFilename()
        );

        Route::prefix($routePrefix)->group(function () use ($files) {
            foreach ($files as $file) {
                require $file->getPathname();
            }
        });
    }
}
