<?php

namespace Laraneat\Modules\Support\Generator;

use Laraneat\Modules\Enums\ModuleComponentTypeEnum;
use Laraneat\Modules\Exceptions\InvalidConfigValue;
use Laraneat\Modules\Module;

class GeneratorHelper
{
    /**
     * Get generator modules path
     *
     * @throws InvalidConfigValue
     */
    public static function getBasePath(): string
    {
        $generatorPath = config("modules.generator.path");

        if (!$generatorPath) {
            throw InvalidConfigValue::makeForNullValue("modules.generator.path");
        }

        return self::formatPath($generatorPath);
    }

    /**
     * Get generator modules namespace
     *
     * @throws InvalidConfigValue
     */
    public static function getBaseNamespace(): string
    {
        $generatorNamespace = config("modules.generator.namespace");

        if (!$generatorNamespace) {
            throw InvalidConfigValue::makeForNullValue("modules.generator.namespace");
        }

        return self::formatNamespace($generatorNamespace);
    }

    /**
     * Get custom stubs path
     */
    public static function getCustomStubsPath(): ?string
    {
        $customStubsPath = config('modules.generator.custom_stubs', base_path('/stubs/modules'));

        return $customStubsPath ? self::formatPath($customStubsPath) : null;
    }

    /**
     * Get user model class
     *
     * @return class-string|null
     */
    public static function getUserModelClass(): ?string
    {
        $userModelClass = config("modules.generator.user_model");

        return $userModelClass && is_string($userModelClass) ? self::formatNamespace($userModelClass) : null;
    }

    /**
     * Get "create permission" action class
     *
     * @return class-string|null
     */
    public static function getCreatePermissionActionClass(): ?string
    {
        $createPermissionActionClass = config("modules.generator.create_permission.action");

        return $createPermissionActionClass && is_string($createPermissionActionClass)
            ? self::formatNamespace($createPermissionActionClass)
            : null;
    }

    /**
     * Get "create permission" DTO class
     *
     * @return class-string|null
     */
    public static function getCreatePermissionDTOClass(): ?string
    {
        $createPermissionDTOClass = config("modules.generator.create_permission.dto");

        return $createPermissionDTOClass && is_string($createPermissionDTOClass)
            ? self::formatNamespace($createPermissionDTOClass)
            : null;
    }

    /**
     * Get component config
     */
    public static function component(ModuleComponentTypeEnum $componentType): ?GeneratorPath
    {
        $generatorComponent = config("modules.generator.components.{$componentType->value}");

        return $generatorComponent ? new GeneratorPath($generatorComponent) : null;
    }

    /**
     * Get module path
     *
     * @throws InvalidConfigValue
     */
    public static function makeModulePath(Module|string $moduleOrName, ?string $subPath = null): ?string
    {
        if ($moduleOrName instanceof Module) {
            return $moduleOrName->subPath($subPath);
        }

        $modulePart = self::formatPath($moduleOrName);

        if (!$modulePart) {
            return null;
        }

        $modulePath = self::getBasePath() . '/' . $modulePart;

        return $subPath ? $modulePath . '/' .  self::formatPath($subPath) : $modulePath;
    }

    /**
     * Get module namespace
     *
     * @throws InvalidConfigValue
     */
    public static function makeModuleNamespace(Module|string $moduleOrName, ?string $subNamespace = null): ?string
    {
        if ($moduleOrName instanceof Module) {
            return $moduleOrName->subNamespace($subNamespace);
        }

        $modulePart = self::formatNamespace($moduleOrName);

        if (!$modulePart) {
            return null;
        }

        $moduleNamespace = self::getBaseNamespace() . '\\' . $modulePart;

        return $subNamespace ? $moduleNamespace . '\\' .  self::formatNamespace($subNamespace) : $moduleNamespace;
    }

    /**
     * Format path (normalize slashes)
     */
    private static function formatPath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * Format namespace (normalize slashes)
     */
    private static function formatNamespace(string $namespace): string
    {
        return trim(str_replace('/', '\\', $namespace), '\\');
    }
}
