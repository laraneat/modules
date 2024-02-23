<?php

namespace Laraneat\Modules\Facades;

use Illuminate\Support\Facades\Facade;
use Laraneat\Modules\Enums\ModuleType;
use Laraneat\Modules\Module;

/**
 * @method static array<int, string> getProviders(ModuleType $type = ModuleType::All)
 * @method static array<string, string> getAliases(ModuleType $type = ModuleType::All)
 * @method static array<int, string> getScanPaths()
 * @method static $this addScanPath(string|string[] $scanPaths)
 * @method static array<string, array> buildAppModulesManifest(bool $shouldCache = true)
 * @method static array<string, array> buildVendorModulesManifest()
 * @method static bool pruneModulesManifest()
 * @method static bool pruneAppModulesManifest()
 * @method static bool pruneVendorModulesManifest()
 * @method static array<string, Module> getAppModules()
 * @method static array<string, Module> getVendorModules()
 * @method static array<string, Module> getAllModules()
 * @method static array<string, Module> getModules(ModuleType $type = ModuleType::All)
 * @method static array<int, string> getPackagesToIgnore()
 * @method static bool has(string $modulePackageName, ModuleType $type = ModuleType::All)
 * @method static int count(ModuleType $type = ModuleType::All)
 * @method static Module|null find(string $modulePackageName, ModuleType $type = ModuleType::All)
 * @method static Module findOrFail(string $modulePackageName, ModuleType $type = ModuleType::All)
 * @method static array<string, Module> filterByName(string $moduleName, ModuleType $type = ModuleType::All)
 * @method static array<string, Module> filterByNameOrFail(string $moduleName, ModuleType $type = ModuleType::All)
 * @method static bool delete(string $modulePackageName, ModuleType $type = ModuleType::All)
 * @method static string getCachedModulesServicesPath()
 * @method static array<string, array> toArray(ModuleType $type = ModuleType::All)
 *
 * @see \Laraneat\Modules\ModulesRepository
 */
class Modules extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'modules';
    }
}
