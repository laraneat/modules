<?php

namespace Laraneat\Modules\Support\Generator;

use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Module;

class GeneratorHelper
{
    /**
     * Get generator modules path
     *
     * @return string
     */
    public static function path(): string
    {
        return rtrim(config("modules.generator.path"), '/');
    }

    /**
     * Get generator modules namespace
     *
     * @return string
     */
    public static function namespace(): string
    {
        return self::formatNamespace(config("modules.generator.namespace"));
    }

    /**
     * Get custom stubs path
     *
     * @return string
     */
    public static function customStubsPath(): string
    {
        return rtrim(config("modules.generator.custom_stubs"), '/');
    }

    /**
     * Get user model
     *
     * @return string
     */
    public static function userModel(): string
    {
        return self::formatNamespace(config("modules.generator.user_model"));
    }

    /**
     * Get "create permission" action
     *
     * @return string
     */
    public static function createPermissionAction(): string
    {
        return self::formatNamespace(config("modules.generator.create_permission_action"));
    }

    /**
     * Get component config
     *
     * @param string $componentType
     *
     * @return GeneratorPath
     */
    public static function component(string $componentType): GeneratorPath
    {
        return new GeneratorPath(config("modules.generator.components.$componentType"));
    }

    /**
     * Get module path
     *
     * @param Module|string $module
     * @param string|null $extraPath
     * @return string
     */
    public static function modulePath(Module|string $module, ?string $extraPath = null): string
    {
        try {
            $modulePath = Modules::getModulePath($module);
        } catch (ModuleNotFoundException $e) {
            $modulesPath = self::path();
            $modulePart = self::formatPath($module);
            $modulePath = $modulePart ? $modulesPath . '/' . $modulePart : $modulePart;
        }

        return $extraPath ? $modulePath . '/' .  self::formatPath($extraPath) : $modulePath;
    }

    /**
     * Get module namespace
     *
     * @param Module|string $module
     * @param string|null $extraNamespace
     * @return string
     */
    public static function moduleNamespace(Module|string $module, ?string $extraNamespace = null): string
    {
        try {
            $moduleNamespace = Modules::getModuleNamespace($module);
        } catch (ModuleNotFoundException $e) {
            $modulesNamespace = self::namespace();
            $modulePart = self::formatNamespace($module);
            $moduleNamespace = $modulePart ? $modulesNamespace . '\\' . $modulePart : $modulePart;
        }

        return $extraNamespace ? $moduleNamespace . '\\' . self::formatNamespace($extraNamespace) : $moduleNamespace;
    }

    /**
     * Format path (normalize slashes)
     *
     * @param string $path
     * @return string
     */
    private static function formatPath(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }

    /**
     * Format namespace (normalize slashes)
     *
     * @param string $namespace
     * @return string
     */
    private static function formatNamespace(string $namespace): string
    {
        return trim(str_replace('/', '\\', $namespace), '\\');
    }
}
