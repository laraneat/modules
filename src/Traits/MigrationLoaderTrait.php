<?php

namespace Laraneat\Modules\Traits;

use Laraneat\Modules\Facades\Modules;

trait MigrationLoaderTrait
{
    /**
     * Include all migrations files from the specified module.
     *
     * @param string $moduleName
     */
    protected function loadMigrationFiles(string $moduleName): void
    {
        $path = Modules::getModulePath($moduleName) . $this->getMigrationGeneratorPath();

        $files = $this->laravel['files']->glob($path . '/*_*.php');

        foreach ($files as $file) {
            $this->laravel['files']->requireOnce($file);
        }
    }

    /**
     * Get migration generator path.
     *
     * @return string
     */
    protected function getMigrationGeneratorPath(): string
    {
        return Modules::config('paths.generator.migration');
    }
}
