<?php

namespace Laraneat\Modules\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static
 * addLocation(string $path)
 * @method static array all()
 * @method static array allDisabled()
 * @method static array allEnabled()
 * @method static array asset()
 * @method static array assetPath()
 * @method static \Laraneat\Modules\Collection collections()
 * @method static array config(bool $status)
 * @method static bool count($name)
 * @method static array delete()
 * @method static array disable()
 * @method static int enable()
 * @method static array find(string $direction = 'asc')
 * @method static string findByAlias()
 * @method static void findOrFail()
 * @method static \Laraneat\Modules\Module|null findRequirements(string $name)
 * @method static \Laraneat\Modules\Module|null forgetUsed(string $alias)
 * @method static \Laraneat\Modules\Module getAssetsPath(string $moduleName)
 * @method static array getByStatus($name)
 * @method static \Laraneat\Modules\Collection getCached(bool $status = true)
 * @method static string getFiles(string $moduleName)
 * @method static string getModulePath(string $moduleName)
 * @method static mixed getOrdered(string $key, $default = null)
 * @method static string getPath()
 * @method static void getPaths(string $moduleName)
 * @method static void getScanPaths()
 * @method static string getStubPath()
 * @method static \Illuminate\Filesystem\Filesystem getUsedNow()
 * @method static string getUsedStoragePath()
 * @method static string has(string $asset)
 * @method static bool install(string $moduleName)
 * @method static bool isDisabled(string $moduleName)
 * @method static void isEnabled(string $moduleName)
 * @method static void register(string $moduleName)
 * @method static bool scan(string $moduleName)
 * @method static void setStubPath(string $module)
 * @method static \Symfony\Component\Process\Process setUsed(string $name, ?string $version = 'dev-master', ?string $type = 'composer', bool $subtree = false)
 * @method static string|null toCollection()
 * @method static update(string $stubPath)
 *
 * @see \Laraneat\Modules\FileRepository
 */
class Modules extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'modules';
    }
}
