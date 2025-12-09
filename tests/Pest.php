<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use function Spatie\Snapshots\assertMatchesFileSnapshot;
use function Spatie\Snapshots\assertMatchesSnapshot;

/**
 * @return array<int, string>
 */
function getRelativeFilePathsInDirectory(string $directory): array
{
    $filesystem = new Illuminate\Filesystem\Filesystem();

    return array_map(fn (SplFileInfo $fileInfo) => $fileInfo->getRelativePathname(), $filesystem->allFiles($directory));
}

/**
 * @param array<int, string> $paths
 * @param string $basePath
 * @return array<int, string>
 */
function makeAbsolutePaths(array $paths, string $basePath): array
{
    $basePath = rtrim($basePath, '\\/');

    return array_map(fn (string $path) => realpath($basePath . '/' . $path), $paths);
}

function assertsMatchesDirectorySnapshot(string $directory): void
{
    expect($directory)->toBeDirectory();
    $filePaths = getRelativeFilePathsInDirectory($directory);
    assertMatchesSnapshot($filePaths);
    foreach (makeAbsolutePaths($filePaths, $directory) as $filePath) {
        assertMatchesFileSnapshot($filePath);
    }
}

uses(Laraneat\Modules\Tests\TestCase::class)
    ->afterEach(function () {
        /** @var \Laraneat\Modules\Tests\TestCase $this */
        $this->pruneModulesPaths();
        $this->resetComposerJson();
    })
    ->in('.');
