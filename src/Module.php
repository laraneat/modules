<?php

declare(strict_types=1);

namespace Laraneat\Modules;

final readonly class Module
{
    /**
     * @param  string  $name  The directory name of the module ("shop-order").
     * @param  string  $package  The Composer package name ("app/shop-order").
     * @param  string  $namespace  The root namespace, without the trailing backslash ("Modules\ShopOrder").
     * @param  string  $path  The absolute path of the module directory.
     * @param  string  $sourcePath  The absolute path of the directory of the root namespace.
     */
    public function __construct(
        public string $name,
        public string $package,
        public string $namespace,
        public string $path,
        public string $sourcePath,
    ) {}
}
