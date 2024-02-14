<?php

use Laraneat\Modules\Facades\Modules;

if (! function_exists('module_path')) {
    /**
     * Get the module path
     *
     * @param string $modulePackageName
     * @param string $path
     *
     * @return string
     */
    function module_path(string $modulePackageName, string $path = ''): string
    {
        return Modules::findOrFail($modulePackageName)->subPath($path);
    }
}
