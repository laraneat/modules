<?php

namespace Laraneat\Modules\Traits;

trait MigrationLoaderTrait
{
    /**
     * Include all migrations files from the specified module.
     *
     * @param string $moduleName
     */
    protected function loadMigrationFiles(string $moduleName): void
    {
        $path = $this->laravel['modules']->getModulePath($moduleName) . $this->getMigrationGeneratorPath();

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
        return $this->laravel['modules']->config('paths.generator.migration');
    }
}
