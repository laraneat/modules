<?php

namespace Laraneat\Modules\Contracts;

use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Collection;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Module;

interface RepositoryInterface
{
    /**
     * Get all modules.
     *
     * @return array<string, Module>
     */
    public function all(): array;

    /**
     * Get cached modules.
     *
     * @return array<string, Module>
     */
    public function getCached(): array;

    /**
     * Scan & get all available modules.
     *
     * @return array<string, Module>
     */
    public function scan(): array;

    /**
     * Get modules as modules collection instance.
     *
     * @return Collection<string, Module>
     */
    public function toCollection(): Collection;

    /**
     * Get scanned paths.
     *
     * @return string[]
     */
    public function getScanPaths(): array;

    /**
     * Get list of enabled modules.
     *
     * @return array<string, Module>
     */
    public function allEnabled(): array;

    /**
     * Get list of disabled modules.
     *
     * @return array<string, Module>
     */
    public function allDisabled(): array;

    /**
     * Get count from all modules.
     */
    public function count(): int;

    /**
     * Get all ordered modules.
     *
     * @param string $direction
     *
     * @return array<string, Module>
     */
    public function getOrdered(string $direction = 'asc'): array;

    /**
     * Get modules by the given status.
     *
     * @param bool $status
     *
     * @return array<string, Module>
     */
    public function getByStatus(bool $status): array;

    /**
     * Find a specific module.
     */
    public function find(string $moduleName): ?Module;

    /**
     * Find all modules that are required by a module. If the module cannot be found, throw an exception.
     *
     * @param string $moduleName
     *
     * @return array<int, Module>
     * @throws ModuleNotFoundException
     */
    public function findRequirements(string $moduleName): array;

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     *
     * @throws ModuleNotFoundException
     */
    public function findOrFail(string $moduleName): Module;

    /**
     * Get path for a specific module.
     */
    public function getModulePath(Module|string $module, ?string $extraPath = null): string;

    /**
     * Get namespace for a specific module.
     */
    public function getModuleNamespace(Module|string $module, ?string $extraNamespace = null): string;

    public function getFilesystem(): Filesystem;

    /**
     * Get a specific config data from a configuration file.
     */
    public function config(string $key, $default = null);

    /**
     * Get default modules path.
     */
    public function getDefaultPath(): string;

    /**
     * Find a specific module by its alias.
     */
    public function findByAlias(string $alias): ?Module;

    /**
     * Boot the modules.
     */
    public function boot(): void;

    /**
     * Register the modules.
     */
    public function register(): void;

    /**
     * Get asset path for a specific module.
     */
    public function assetPath(string $moduleName): string;

    /**
     * Delete a specific module.
     *
     * @throws ModuleNotFoundException
     */
    public function delete(string $moduleName): bool;

    /**
     * Determine whether the given module is activated.
     *
     * @throws ModuleNotFoundException
     */
    public function isEnabled(string $moduleName): bool;

    /**
     * Determine whether the given module is not activated.
     *
     * @throws ModuleNotFoundException
     */
    public function isDisabled(string $moduleName): bool;
}
