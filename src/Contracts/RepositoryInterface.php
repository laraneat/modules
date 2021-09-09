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
     * @return array
     */
    public function all(): array;

    /**
     * Get cached modules.
     *
     * @return array
     */
    public function getCached(): array;

    /**
     * Scan & get all available modules.
     *
     * @return array
     */
    public function scan(): array;

    /**
     * Get modules as modules collection instance.
     *
     * @return Collection
     */
    public function toCollection(): Collection;

    /**
     * Get scanned paths.
     *
     * @return array
     */
    public function getScanPaths(): array;

    /**
     * Get list of enabled modules.
     *
     * @return array
     */
    public function allEnabled(): array;

    /**
     * Get list of disabled modules.
     *
     * @return array
     */
    public function allDisabled(): array;

    /**
     * Get count from all modules.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Get all ordered modules.
     *
     * @param string $direction
     *
     * @return array
     */
    public function getOrdered(string $direction = 'asc'): array;

    /**
     * Get modules by the given status.
     *
     * @param bool $status
     *
     * @return mixed
     */
    public function getByStatus(bool $status): array;

    /**
     * Find a specific module.
     *
     * @param string $moduleName
     *
     * @return Module|null
     */
    public function find(string $moduleName): ?Module;

    /**
     * Find all modules that are required by a module. If the module cannot be found, throw an exception.
     *
     * @param string $moduleName
     *
     * @return array
     * @throws ModuleNotFoundException
     */
    public function findRequirements(string $moduleName): array;

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     *
     * @param string $moduleName
     *
     * @return Module
     */
    public function findOrFail(string $moduleName): Module;

    public function getModulePath(string $moduleName);

    /**
     * @return Filesystem
     */
    public function getFiles(): Filesystem;

    /**
     * Get a specific config data from a configuration file.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function config(string $key, $default = null);

    /**
     * Get a module path.
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Find a specific module by its alias.
     *
     * @param string $alias
     *
     * @return Module|void
     */
    public function findByAlias(string $alias);

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
     *
     * @param string $moduleName
     *
     * @return string
     */
    public function assetPath(string $moduleName): string;

    /**
     * Delete a specific module.
     *
     * @param string $moduleName
     *
     * @return bool
     * @throws ModuleNotFoundException
     */
    public function delete(string $moduleName): bool;

    /**
     * Determine whether the given module is activated.
     *
     * @param string $moduleName
     *
     * @return bool
     * @throws ModuleNotFoundException
     */
    public function isEnabled(string $moduleName): bool;

    /**
     * Determine whether the given module is not activated.
     *
     * @param string $moduleName
     *
     * @return bool
     * @throws ModuleNotFoundException
     */
    public function isDisabled(string $moduleName): bool;
}
