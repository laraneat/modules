<?php

namespace Laraneat\Modules\Concerns;

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

        /** @var SplFileInfo[] $files */
        $files = Arr::sort(File::files($directory), function (SplFileInfo $file) {
            return $file->getFilename();
        });

        Route::prefix($routePrefix)->group(function () use ($files) {
            foreach ($files as $file) {
                require $file->getPathname();
            }
        });
    }
}
