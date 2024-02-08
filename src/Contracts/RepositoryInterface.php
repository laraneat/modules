<?php

namespace Laraneat\Modules\Contracts;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Module;

interface RepositoryInterface
{
    /**
     * Register the modules.
     */
    public function register(): void;

    /**
     * Boot the modules.
     */
    public function boot(): void;

    /**
     * Get all modules.
     *
     * @return array<string, Module>
     */
    public function all(): array;

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
     * Get cached modules.
     *
     * @return array<string, Module>
     */
    public function getCached(): array;

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
     * Scan & get all available modules.
     *
     * @return array<string, Module>
     */
    public function scan(): array;

    /**
     * Determine whether the given module exist.
     */
    public function has(string $moduleName): bool;

    /**
     * Get count from all modules.
     */
    public function count(): int;

    /**
     * Find a specific module.
     */
    public function find(string $moduleName): ?Module;

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     *
     * @throws ModuleNotFoundException
     */
    public function findOrFail(string $moduleName): Module;

    /**
     * Find a specific module by its alias.
     */
    public function findByAlias(string $alias): ?Module;

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

    /**
     * Enable a specific module.
     *
     * @throws ModuleNotFoundException
     */
    public function enable(string $moduleName): void;

    /**
     * Disable a specific module.
     *
     * @throws ModuleNotFoundException
     */
    public function disable(string $moduleName): void;

    /**
     * Delete a specific module.
     *
     * @throws ModuleNotFoundException
     */
    public function delete(string $moduleName): bool;

    /**
     * Update dependencies for the specified module.
     *
     * @throws ModuleNotFoundException
     */
    public function update(string $moduleName): void;

    /**
     * Get storage path for module used.
     */
    public function getUsedStoragePath(): string;

    /**
     * Set module used for cli session.
     *
     * @throws ModuleNotFoundException
     */
    public function setUsed(string $moduleName): void;

    /**
     * Forget the module used for cli session.
     */
    public function forgetUsed(): void;

    /**
     * Get module used for cli session.
     */
    public function getUsedNow(): Module;

    /**
     * Get modules as modules collection instance.
     *
     * @return Collection<string, Module>
     */
    public function toCollection(): Collection;

    /**
     * Get path for a specific module.
     */
    public function getModuleKey(Module|string $module): string;

    /**
     * Get path for a specific module.
     */
    public function getModulePath(Module|string $module, ?string $extraPath = null): string;

    /**
     * Get namespace for a specific module.
     */
    public function getModuleNamespace(Module|string $module, ?string $extraNamespace = null): string;

    /**
     * Get scanned paths.
     *
     * @return string[]
     */
    public function getScanPaths(): array;

    /**
     * Add scan path.
     */
    public function addScanPath(string $path): static;

    /**
     * Get asset path for a specific module.
     */
    public function assetPath(string $moduleName): string;

    /**
     * Get a specific config data from a configuration file.
     */
    public function config(string $key, mixed $default = null): mixed;
}
