<?php

use Laraneat\Modules\Facades\Modules;

if (! function_exists('module_path')) {
    /**
     * Get the module path
     *
     * @param string $moduleName
     * @param string $path
     *
     * @return string
     */
    function module_path(string $moduleName, string $path = ''): string
    {
        $module = Modules::findOrFail($moduleName);

        return $module->getExtraPath($path);
    }
}
