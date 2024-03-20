<?php

namespace Laraneat\Modules\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModulesRepository;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method static array<int, string> getScanPaths()
 * @method static $this addScanPath(string|string[] $scanPaths)
 * @method static array<string, array> buildModulesManifest()
 * @method static bool pruneModulesManifest()
 * @method static array<string, Module> getModules()
 * @method static array<int, string> getPackagesToIgnore()
 * @method static bool has(string $modulePackageName)
 * @method static int count()
 * @method static Module|null find(string $modulePackageName)
 * @method static Module findOrFail(string $modulePackageName)
 * @method static array<string, Module> filterByName(string $moduleName)
 * @method static array<string, Module> filterByNameOrFail(string $moduleName)
 * @method static bool delete(string $modulePackageName)
 * @method static void syncWithComposer(\Closure|OutputInterface $output = null)
 * @method static array<string, array> toArray()
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
        return ModulesRepository::class;
    }
}
