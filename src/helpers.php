<?php

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
        /** @var $module \Laraneat\Modules\Module */
        $module = app('modules')->find($moduleName);

        return $module->getPath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}
