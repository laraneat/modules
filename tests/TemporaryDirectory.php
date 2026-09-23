<?php

declare(strict_types=1);

namespace Laraneat\Modules\Tests;

use Illuminate\Filesystem\Filesystem;

/**
 * A directory outside of the repository that is removed after the test, even when it fails.
 */
final class TemporaryDirectory
{
    public static function create(?string $copyOf = null): string
    {
        $path = sys_get_temp_dir().'/laraneat-'.bin2hex(random_bytes(8));

        $copyOf === null ? mkdir($path, 0777, true) : (new Filesystem)->copyDirectory($copyOf, $path);

        return str_replace('\\', '/', (string) realpath($path));
    }

    public static function delete(string $path): void
    {
        (new Filesystem)->deleteDirectory($path);
    }
}
