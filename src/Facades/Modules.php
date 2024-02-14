<?php

namespace Laraneat\Modules\Facades;

use Illuminate\Support\Facades\Facade;
use Laraneat\Modules\Module;

/**
 * @method static array<int, string> getProviders()
 * @method static array<string, string> getAliases()
 * @method static array<int, string> getScanPaths()
 * @method static $this addScanPath(string|string[] $scanPaths)
 * @method static array<string, array> buildAppModulesManifest(bool $shouldCache = true)
 * @method static array<string, array> buildVendorModulesManifest()
 * @method static bool pruneAppModulesManifest()
 * @method static bool pruneVendorModulesManifest()
 * @method static array<string, Module> getAppModules()
 * @method static array<string, Module> getVendorModules()
 * @method static array<string, Module> getModules()
 * @method static array<int, string> getPackagesToIgnore()
 * @method static bool has(string $modulePackageName)
 * @method static int count()
 * @method static Module|null find(string $modulePackageName)
 * @method static Module findOrFail(string $modulePackageName)
 * @method static array<string, Module> filterByName(string $moduleName)
 * @method static array<string, Module> filterByNameOrFail(string $moduleName)
 * @method static bool delete(string $modulePackageName)
 * @method static string getCachedModulesServicesPath()
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
