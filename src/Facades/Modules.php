<?php

namespace Laraneat\Modules\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void register()
 * @method static void boot()
 * @method static string getDefaultPath()
 * @method static string[] getPaths()
 * @method static $this addLocation(string $path)
 * @method static string[] getScanPaths()
 * @method static string getModulePath(\Laraneat\Modules\Module|string $module, ?string $extraPath = null)
 * @method static string getModuleNamespace(\Laraneat\Modules\Module|string $module, ?string $extraNamespace = null)
 * @method static \Laraneat\Modules\Module[] all()
 * @method static \Laraneat\Modules\Module[] allEnabled()
 * @method static \Laraneat\Modules\Module[] allDisabled()
 * @method static \Laraneat\Modules\Module[] getCached()
 * @method static \Laraneat\Modules\Module[] getOrdered(string $direction = 'asc')
 * @method static \Laraneat\Modules\Module[] getByStatus(bool $status)
 * @method static \Laraneat\Modules\Module[] scan()
 * @method static \Illuminate\Support\Collection toCollection()
 * @method static bool has(string $moduleName)
 * @method static int count()
 * @method static \Laraneat\Modules\Module|null find(string $moduleName)
 * @method static \Laraneat\Modules\Module|null findByAlias(string $alias)
 * @method static \Laraneat\Modules\Module findOrFail(string $moduleName)
 * @method static \Laraneat\Modules\Module[] findRequirements(string $moduleName)
 * @method static \Illuminate\Support\Collection collections(bool $status = true)
 * @method static string assetPath(string $moduleName)
 * @method static mixed config(string $key, $default = null)
 * @method static string getUsedStoragePath()
 * @method static void setUsed(string $moduleName)
 * @method static void forgetUsed()
 * @method static \Laraneat\Modules\Module getUsedNow()
 * @method static \Illuminate\Filesystem\Filesystem getFilesystem()
 * @method static string getAssetsPath()
 * @method static string asset(string $asset)
 * @method static bool isEnabled(string $moduleName)
 * @method static bool isDisabled(string $moduleName)
 * @method static void enable(string $moduleName)
 * @method static void disable(string $moduleName)
 * @method static bool delete(string $moduleName)
 * @method static void update(string $moduleName)
 * @method static \Symfony\Component\Process\Process install(string $name, ?string $version = 'dev-master', ?string $type = 'composer', bool $subtree = false)
 * @method static void flushCache()
 *
 * @see \Laraneat\Modules\FileRepository
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
