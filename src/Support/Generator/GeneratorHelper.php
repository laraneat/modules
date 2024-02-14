<?php

namespace Laraneat\Modules\Support\Generator;

use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Module;

class GeneratorHelper
{
    /**
     * Get generator modules path
     */
    public static function path(): string
    {
        return rtrim(config("modules.generator.path"), '/');
    }

    /**
     * Get generator modules namespace
     */
    public static function namespace(): string
    {
        return self::formatNamespace(config("modules.generator.namespace"));
    }

    /**
     * Get custom stubs path
     */
    public static function customStubsPath(): string
    {
        return rtrim(config("modules.generator.custom_stubs"), '/');
    }

    /**
     * Get user model
     */
    public static function userModel(): string
    {
        return self::formatNamespace(config("modules.generator.user_model"));
    }

    /**
     * Get "create permission" action
     */
    public static function createPermissionAction(): string
    {
        return self::formatNamespace(config("modules.generator.create_permission.action"));
    }

    /**
     * Get "create permission" DTO
     */
    public static function createPermissionDTO(): string
    {
        return self::formatNamespace(config("modules.generator.create_permission.dto"));
    }

    /**
     * Get component config
     */
    public static function component(string $componentType): GeneratorPath
    {
        return new GeneratorPath(config("modules.generator.components.$componentType"));
    }

    /**
     * Get module path
     */
    public static function modulePath(Module|string $module, ?string $extraPath = null): string
    {
        try {
            $modulePath = Modules::findOrFail($module)->getPath();
        } catch (ModuleNotFoundException $e) {
            $modulesPath = self::path();
            $modulePart = self::formatPath($module);
            $modulePath = $modulePart ? $modulesPath . '/' . $modulePart : $modulePart;
        }

        return $extraPath ? $modulePath . '/' .  self::formatPath($extraPath) : $modulePath;
    }

    /**
     * Get module namespace
     */
    public static function modulePackageNamespace(Module|string $module, ?string $extraNamespace = null): string
    {
        try {
            $modulePackageNamespace = Modules::findOrFail($module)->getNamespace();
        } catch (ModuleNotFoundException $e) {
            $modulesNamespace = self::namespace();
            $modulePart = self::formatNamespace($module);
            $modulePackageNamespace = $modulePart ? $modulesNamespace . '\\' . $modulePart : $modulePart;
        }

        return $extraNamespace ? $modulePackageNamespace . '\\' . self::formatNamespace($extraNamespace) : $modulePackageNamespace;
    }

    /**
     * Format path (normalize slashes)
     */
    private static function formatPath(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }

    /**
     * Format namespace (normalize slashes)
     */
    private static function formatNamespace(string $namespace): string
    {
        return trim(str_replace('/', '\\', $namespace), '\\');
    }
}
