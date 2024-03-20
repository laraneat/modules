<?php

namespace Laraneat\Modules\Support\Generator;

use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleComponentType;
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
        $generatorPath = config('modules.path');

        if (! $generatorPath) {
            throw InvalidConfigValue::makeForNullValue('modules.path');
        }

        return self::normalizePath($generatorPath, true);
    }

    /**
     * Get generator modules namespace
     *
     * @throws InvalidConfigValue
     */
    public static function getBaseNamespace(): string
    {
        $generatorNamespace = config('modules.namespace');

        if (! $generatorNamespace) {
            throw InvalidConfigValue::makeForNullValue('modules.namespace');
        }

        return self::normalizeNamespace($generatorNamespace);
    }

    /**
     * Get custom stubs path
     */
    public static function getCustomStubsPath(): ?string
    {
        $customStubsPath = config('modules.custom_stubs', base_path('/stubs/modules'));

        return $customStubsPath ? self::normalizePath($customStubsPath, true) : null;
    }

    /**
     * Get user model class
     *
     * @return class-string|null
     */
    public static function getUserModelClass(): ?string
    {
        $userModelClass = config('modules.user_model');

        return $userModelClass && is_string($userModelClass) ? self::normalizeNamespace($userModelClass) : null;
    }

    /**
     * Get "create permission" action class
     *
     * @return class-string|null
     */
    public static function getCreatePermissionActionClass(): ?string
    {
        $createPermissionActionClass = config('modules.create_permission.action');

        return $createPermissionActionClass && is_string($createPermissionActionClass)
            ? self::normalizeNamespace($createPermissionActionClass)
            : null;
    }

    /**
     * Get "create permission" DTO class
     *
     * @return class-string|null
     */
    public static function getCreatePermissionDTOClass(): ?string
    {
        $createPermissionDTOClass = config('modules.create_permission.dto');

        return $createPermissionDTOClass && is_string($createPermissionDTOClass)
            ? self::normalizeNamespace($createPermissionDTOClass)
            : null;
    }

    /**
     * Get component config
     *
     *  @throws InvalidConfigValue
     */
    public static function component(ModuleComponentType $componentType): GeneratorPath
    {
        $configPath = "modules.components.{$componentType->value}";
        $generatorComponent = config($configPath);

        if (! is_array($generatorComponent) || empty($generatorComponent['path'])) {
            throw InvalidConfigValue::make($configPath);
        };

        return new GeneratorPath($generatorComponent);
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

        $modulePart = self::normalizePath($moduleOrName);

        if (! $modulePart) {
            return null;
        }

        $modulePath = self::getBasePath() . '/' . $modulePart;

        return $subPath ? $modulePath . '/' .  self::normalizePath($subPath) : $modulePath;
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

        $modulePart = self::normalizeNamespace(Str::studly($moduleOrName));

        if (! $modulePart) {
            return null;
        }

        $moduleNamespace = self::getBaseNamespace() . '\\' . $modulePart;

        return $subNamespace ? $moduleNamespace . '\\' .  self::normalizeNamespace($subNamespace) : $moduleNamespace;
    }

    /**
     * Make relative path or returns null
     */
    public static function makeRelativePath(string $from, string $to): ?string
    {
        $from = static::normalizePath($from, true);
        $to = static::normalizePath($to, true);

        if ($from === $to) {
            return '';
        }

        $fromSegments = explode('/', $from);
        $toSegments = explode('/', $to);

        $diffStartIndex = null;
        $fromSegmentsCount = count($fromSegments);
        $toSegmentsCount = count($toSegments);
        $segmentsCount = max($fromSegmentsCount, $toSegmentsCount);
        for ($i = 0; $i < $segmentsCount; $i++) {
            if (! isset($fromSegments[$i], $toSegments[$i]) || $fromSegments[$i] !== $toSegments[$i]) {
                if ($i === 0 || $i === 1 && ! $fromSegments[0]) {
                    return null;
                }
                $diffStartIndex = $i;

                break;
            }
        }

        $relativePath = Str::repeat('../', $fromSegmentsCount - $diffStartIndex)
            . join('/', array_slice($toSegments, $diffStartIndex));

        return rtrim($relativePath, '/');
    }

    /**
     * Normalize path to use only forward slash and trim slashes
     */
    public static function normalizePath(string $path, $useRtrim = false): string
    {
        $path = str_replace('\\', '/', $path);

        return $useRtrim && Str::startsWith($path, '/')
            ? '/' . trim($path, '/')
            : trim($path, '/');
    }

    /**
     * Normalize namespace to use only backslash and trim slashes
     */
    public static function normalizeNamespace(string $namespace, $useRtrim = false): string
    {
        $namespace = str_replace('/', '\\', $namespace);

        return $useRtrim && Str::startsWith($namespace, '\\')
            ? '\\' . trim($namespace, '\\')
            : trim($namespace, '\\');
    }
}
