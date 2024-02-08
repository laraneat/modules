<?php

namespace Laraneat\Modules\Facades;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Laraneat\Modules\Module;
use Symfony\Component\Process\Process;

/**
 *
 * @method static void register()
 * @method static void boot()
 * @method static array<string, Module> all()
 * @method static array<string, Module> allEnabled()
 * @method static array<string, Module> allDisabled()
 * @method static array<string, Module> getCached()
 * @method static array<string, Module> getOrdered(string $direction = 'asc')
 * @method static array<string, Module> getByStatus(bool $status)
 * @method static array<string, Module> scan()
 * @method static array<string, array> scanRaw()
 * @method static bool has(string $moduleName)
 * @method static int count()
 * @method static Module|null find(string $moduleName)
 * @method static Module|null findByAlias(string $alias)
 * @method static Module findOrFail(string $moduleName)
 * @method static array<int, Module> findRequirements(string $moduleName)
 * @method static bool isEnabled(string $moduleName)
 * @method static bool isDisabled(string $moduleName)
 * @method static void enable(string $moduleName)
 * @method static void disable(string $moduleName)
 * @method static bool delete(string $moduleName)
 * @method static void update(string $moduleName)
 * @method static Process install(string $name, ?string $version = 'dev-master', ?string $type = 'composer', bool $subtree = false)
 * @method static string getUsedStoragePath()
 * @method static void setUsed(string $moduleName)
 * @method static void forgetUsed()
 * @method static Module getUsedNow()
 * @method static void flushCache()
 * @method static Collection<string, Module> toCollection()
 * @method static string getModuleKey(Module|string $module)
 * @method static string getModulePath(Module|string $module, ?string $extraPath = null)
 * @method static string getModuleNamespace(Module|string $module, ?string $extraNamespace = null)
 * @method static array<int, string> getScanPaths()
 * @method static void addScanPath(string $path)
 * @method static string assetPath(string $moduleName)
 * @method static mixed config(string $key, mixed $default = null)
 * @method static Filesystem getFilesystem()
 * @method static string getAssetsPath()
 * @method static string asset(string $asset)
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
