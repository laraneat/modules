<?php

namespace Laraneat\Modules;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Enums\ModuleTypeEnum;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use function Illuminate\Filesystem\join_paths;
use Illuminate\Support\Arr;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Laraneat\Modules\Exceptions\MissingModuleAttribute;
use Laraneat\Modules\Exceptions\ModuleNotFound;

class ModulesRepository implements Arrayable
{
    /**
     * The filesystem instance.
     */
    protected Filesystem $filesystem;

    /**
     * The config repository instance.
     */
    protected Repository $configRepository;

    /**
     * The vendor path.
     */
    protected string $vendorPath;

    /**
     * Paths for scanning modules.
     *
     * @var array<int, string>
     */
    protected array $scanPaths = [];

    /**
     * All loaded modules array.
     *
     * @var array<string, Module>
     */
    protected ?array $allModules = null;

    /**
     * The loaded app modules array.
     *
     * @var array<string, Module>
     */
    protected ?array $appModules = null;

    /**
     * The loaded vendor modules array.
     *
     * @var array<string, Module>
     */
    protected ?array $vendorModules = null;

    /**
     * Module package names that will be ignored.
     *
     * @var array<int, string>
     */
    protected ?array $packagesToIgnore = null;

    /**
     * Create a new module manifest instance.
     */
    public function __construct(
        protected Application $app,
        protected ?string $basePath = null,
        protected ?string $appModulesManifestPath = null,
        protected ?string $vendorModulesManifestPath = null,
    ) {
        $this->basePath = $basePath ?? $app->basePath();
        $this->appModulesManifestPath = $appModulesManifestPath ?? $this->normalizeCachePath('LARANEAT_APP_MODULES_CACHE', 'cache/laraneat-app-modules.php');
        $this->vendorModulesManifestPath = $vendorModulesManifestPath ?? $this->normalizeCachePath('LARANEAT_VENDOR_MODULES_CACHE', 'cache/laraneat-vendor-modules.php');
        $this->filesystem = $this->app['files'];
        $this->configRepository = $this->app['config'];
        $this->vendorPath = Env::get('COMPOSER_VENDOR_DIR') ?: $this->basePath . '/vendor';
        $this->addScanPath($this->configRepository->get('modules.paths.modules', []));
    }

    /**
     * Get all service provider class names for all modules.
     *
     * @return array<int, class-string>
     */
    public function getProviders(ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): array
    {
        return collect($this->getModules($typeEnum))
            ->map(static fn (Module $module) => $module->getProviders())
            ->collapse()
            ->all();
    }

    /**
     * Get all aliases for all packages.
     *
     * @return array<string, class-string>
     */
    public function getAliases(ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): array
    {
        /** @phpstan-ignore-next-line  */
        return collect($this->getModules($typeEnum))
            ->map(static fn (Module $module) => $module->getAliases())
            ->collapse()
            ->all();
    }

    /**
     * Get module scan paths.
     *
     * @return array<int, string>
     */
    public function getScanPaths(): array
    {
        return $this->scanPaths;
    }

    /**
     * Add module scan path.
     *
     * @param string|array<int, string> $scanPaths
     *
     * @return $this
     */
    public function addScanPath(string|array $scanPaths): static
    {
        foreach(Arr::wrap($scanPaths) as $scanPath) {
            $normalizedScanPath = $this->normalizeScanPath($scanPath);

            if (! $normalizedScanPath || in_array($normalizedScanPath, $this->scanPaths)) {
                continue;
            }

            $this->scanPaths[] = $normalizedScanPath;
        }

        $this->appModules = null;

        return $this;
    }

    /**
     * Build the app modules manifest and write it to disk if cache enabled.
     *
     * @return array<string, array{ path: string, isVendor: bool, name: string, namespace: string, providers?: class-string[], aliases?: array<string, class-string> }>
     *
     * @throws MissingModuleAttribute
     * @throws ModuleHasNonUniquePackageName
     */
    public function buildAppModulesManifest(bool $shouldCache = true): array
    {
        $packagesToIgnore = $this->getPackagesToIgnore();

        if (in_array('*', $packagesToIgnore)) {
            return [];
        }

        $appModulesManifest = [];

        foreach($this->scanPaths as $path) {
            $packagePaths = $this->filesystem->glob("$path/composer.json");

            foreach ($packagePaths as $packagePath) {
                $package = json_decode($this->filesystem->get($packagePath), true);
                $packageName = $this->formatPackageName($package['name'] ?? "");
                $moduleData = $package['extra']['laraneat']['module'] ?? [];

                if ($moduleData && $packageName && ! in_array($packageName, $packagesToIgnore)) {
                    if (array_key_exists($packageName, $appModulesManifest)) {
                        throw ModuleHasNonUniquePackageName::make($packageName);
                    }
                    $moduleData['path'] = dirname($packagePath);
                    $moduleData['isVendor'] = false;
                    $appModulesManifest[$packageName] = $this->validateModuleData($packageName, $moduleData);
                }
            }
        }

        if ($shouldCache) {
            $this->write($appModulesManifest, $this->appModulesManifestPath);
        }

        return $appModulesManifest;
    }

    /**
     * Build the vendor modules manifest and write it to disk.
     *
     * @return array<string, array{ path: string, isVendor: bool, name: string, namespace: string, providers?: class-string[], aliases?: array<string, class-string> }>
     *
     * @throws MissingModuleAttribute
     * @throws ModuleHasNonUniquePackageName
     */
    public function buildVendorModulesManifest(): array
    {
        $packagesToIgnore = $this->getPackagesToIgnore();

        if (in_array('*', $packagesToIgnore)) {
            return [];
        }

        $installedPath = $this->vendorPath . '/composer/installed.json';
        $vendorModulesManifest = [];

        if ($this->filesystem->exists($installedPath)) {
            /** @var array{packages?: array} $installed */
            $installed = json_decode($this->filesystem->get($installedPath), true);

            foreach($installed['packages'] ?? [] as $package) {
                $packageName = $this->formatPackageName($package['name'] ?? "");
                $moduleData = $package['extra']['laraneat']['module'] ?? [];

                if ($moduleData && $packageName && ! in_array($packageName, $packagesToIgnore)) {
                    $modulePath = realpath($this->vendorPath . '/composer/' . $package['install-path']);
                    if ($modulePath) {
                        $moduleData['path'] = $modulePath;
                        $moduleData['isVendor'] = true;
                        $vendorModulesManifest[$packageName] = $this->validateModuleData($packageName, $moduleData);
                    }
                }
            }
        }

        $this->write($vendorModulesManifest, $this->vendorModulesManifestPath);

        return $vendorModulesManifest;
    }

    /**
     * Prune the manifests of modules
     */
    public function pruneModulesManifest(): void
    {
        $this->pruneAppModulesManifest();
        $this->pruneVendorModulesManifest();
    }

    /**
     * Prune the manifest of app modules
     */
    public function pruneAppModulesManifest(): bool
    {
        $this->allModules = $this->appModules = null;
        return $this->filesystem->delete($this->appModulesManifestPath);
    }

    /**
     * Prune the manifest of vendor modules
     */
    public function pruneVendorModulesManifest(): bool
    {
        $this->allModules = $this->vendorModules = null;
        return $this->filesystem->delete($this->vendorModulesManifestPath);
    }

    /**
     * Get the current app modules.
     *
     * @return array<string, Module>
     *
     * @throws MissingModuleAttribute
     * @throws ModuleHasNonUniquePackageName
     */
    public function getAppModules(): array
    {
        if ($this->appModules !== null) {
            return $this->appModules;
        }

        $shouldCache = (bool) $this->configRepository->get('modules.cache.enabled', false);

        if ($shouldCache && $this->filesystem->isFile($this->appModulesManifestPath)) {
            return $this->appModules = $this->makeModulesFromManifest(
                $this->filesystem->getRequire($this->appModulesManifestPath)
            );
        }

        return $this->appModules = $this->makeModulesFromManifest($this->buildAppModulesManifest($shouldCache));
    }

    /**
     * Get the current vendor modules.
     *
     * @return array<string, Module>
     *
     * @throws MissingModuleAttribute
     * @throws ModuleHasNonUniquePackageName
     */
    public function getVendorModules(): array
    {
        if ($this->vendorModules !== null) {
            return $this->vendorModules;
        }

        if ($this->filesystem->isFile($this->vendorModulesManifestPath)) {
            return $this->vendorModules = $this->makeModulesFromManifest(
                $this->filesystem->getRequire($this->vendorModulesManifestPath)
            );
        }

        return $this->vendorModules = $this->makeModulesFromManifest($this->buildVendorModulesManifest());
    }

    /**
     * Get all discovered modules
     *
     * @return array<string, Module>
     *
     * @throws MissingModuleAttribute
     * @throws ModuleHasNonUniquePackageName
     */
    public function getAllModules(): array
    {
        if ($this->allModules !== null) {
            return $this->allModules;
        }

        if ($intersectingModules = array_intersect_key($this->getVendorModules(), $this->getAppModules())) {
            throw ModuleHasNonUniquePackageName::make(array_keys($intersectingModules)[0]);
        }

        return $this->allModules = array_merge($this->getVendorModules(), $this->getAppModules());
    }

    /**
     * Get discovered modules by type
     *
     * @return array<string, Module>
     *
     * @throws MissingModuleAttribute
     * @throws ModuleHasNonUniquePackageName
     */
    public function getModules(ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): array
    {
        return match ($typeEnum) {
            ModuleTypeEnum::All => $this->getAllModules(),
            ModuleTypeEnum::App => $this->getAppModules(),
            ModuleTypeEnum::Vendor => $this->getVendorModules(),
        };
    }

    /**
     * Get all package names that should be ignored.
     */
    public function getPackagesToIgnore(): array
    {
        if ($this->packagesToIgnore !== null) {
            return $this->packagesToIgnore;
        }

        $composerPath = $this->basePath . '/composer.json';

        if (! is_file($composerPath)) {
            return [];
        }

        $composerArray = json_decode(file_get_contents($composerPath), true);
        return $this->packagesToIgnore = $composerArray['extra']['laraneat']['dont-discover'] ?? [];
    }

    /**
     * Determine whether the given module exist by its package name.
     */
    public function has(string $modulePackageName, ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): bool
    {
        return array_key_exists($modulePackageName, $this->getModules($typeEnum));
    }

    /**
     * Get count from all modules.
     */
    public function count(ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): int
    {
        return count($this->getModules($typeEnum));
    }

    /**
     * Find a specific module by its package name.
     */
    public function find(string $modulePackageName, ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): ?Module
    {
        $modulePackageName = trim($modulePackageName);

        return $this->getModules($typeEnum)[$modulePackageName] ?? null;
    }

    /**
     * Find a specific module by its package name, if there return that, otherwise throw exception.
     *
     * @throws ModuleNotFound
     */
    public function findOrFail(string $modulePackageName, ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): Module
    {
        $module = $this->find($modulePackageName, $typeEnum);

        if ($module === null) {
            throw ModuleNotFound::make($modulePackageName);
        }

        return $module;
    }

    /**
     * Delete a specific module by its package name.
     *
     * @throws ModuleNotFound
     */
    public function delete(string $modulePackageName, ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): bool
    {
        return $this->findOrFail($modulePackageName, $typeEnum)->delete();
    }

    /**
     * Filter modules by name.
     *
     * @return array<string, Module>
     */
    public function filterByName(string $moduleName, ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): array
    {
        $moduleName = trim($moduleName);
        $modules = [];

        foreach($this->getModules($typeEnum) as $modulePackageName => $module) {
            if ($module->getName() === $moduleName) {
                $modules[$modulePackageName] = $module;
            }
        }

        return $modules;
    }

    /**
     * Find a specific module by its name, if there return that, otherwise throw exception.
     *
     * @return array<string, Module>
     *
     * @throws ModuleNotFound
     */
    public function filterByNameOrFail(string $moduleName, ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): array
    {
        $modules = $this->filterByName($moduleName, $typeEnum);

        if (!$modules) {
            throw ModuleNotFound::makeForName($moduleName);
        }

        return $modules;
    }

    /**
     * Get the path to the cached modules services file.
     */
    public function getCachedModulesServicesPath(): string
    {
        return $this->normalizeCachePath('LARANEAT_MODULES_SERVICES_CACHE', 'cache/laraneat-modules-services.php');
    }

    /**
     * @return array<string, array{
     *     isVendor: bool,
     *     packageName: string,
     *     name: string,
     *     path: string,
     *     namespace: string,
     *     providers: array<int, class-string>,
     *     aliases: array<string, class-string>
     * }>
     */
    public function toArray(ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): array
    {
        return array_map(static fn (Module $module) => $module->toArray(), $this->getModules($typeEnum));
    }

    /**
     * Write the given manifest array to disk.
     *
     * @throws Exception
     */
    protected function write(array $manifest, string $manifestPath): void
    {
        if (! is_writable($dirname = dirname($manifestPath))) {
            throw new Exception("The {$dirname} directory must be present and writable.");
        }

        $this->filesystem->replace(
            $manifestPath,
            '<?php return '.var_export($manifest, true).';'
        );
    }

    /**
     * Format the given package name.
     */
    protected function formatPackageName(string $packageName): string
    {
        return str_replace($this->vendorPath . '/', '', trim($packageName));
    }

    protected function normalizeScanPath(string $path): ?string
    {
        $path = rtrim($path, '/\\');

        if (Str::startsWith($path, $this->vendorPath)) {
            return null;
        }

        return Str::endsWith($path, '/*') ? $path : Str::finish($path, '/*');
    }

    /**
     * Normalize a relative or absolute path to a cache file.
     */
    protected function normalizeCachePath(string $key, string $default): string
    {
        if (is_null($env = Env::get($key))) {
            return $this->app->bootstrapPath($default);
        }

        return Str::startsWith($env, ['/', '\\'])
            ? $env
            : join_paths($this->basePath, $env);
    }

    /**
     * @param string $packageName
     * @param array{ path: string, isVendor: bool, name?: string|null, namespace?: string, providers?: class-string[], aliases?: array<string, class-string> } $moduleData
     *
     * @return array{ path: string, isVendor: bool, name?: string|null, namespace: string, providers?: class-string[], aliases?: array<string, class-string> }
     *
     * @throws MissingModuleAttribute
     */
    protected function validateModuleData(string $packageName, array $moduleData): array
    {
        if (! isset($moduleData['namespace']) || ! trim($moduleData['namespace'])) {
            throw MissingModuleAttribute::make('namespace', $packageName);
        }

        return $moduleData;
    }

    /**
     * Get only vendor modules
     *
     * @param array<string, array{ path: string, isVendor: bool, name?: string|null, namespace: string, providers?: class-string[], aliases?: array<string, class-string> }> $manifest
     *
     * @return array<string, Module>
     */
    protected function makeModulesFromManifest(array $manifest): array
    {
        return collect($manifest)
            ->map(fn ($module, $packageName) => $this->makeModuleFromManifestItem($packageName, $module))
            ->all();
    }

    /**
     * @param string $packageName
     * @param array{ path: string, isVendor: bool, namespace: string, name?: string|null, providers?: class-string[], aliases?: array<string, class-string> } $moduleData
     * @return Module
     */
    protected function makeModuleFromManifestItem(string $packageName, array $moduleData): Module
    {
        return new Module(
            app: $this->app,
            modulesRepository: $this,
            isVendor:$moduleData['isVendor'],
            packageName: $packageName,
            name: $moduleData['name'] ?? null,
            path: $moduleData['path'],
            namespace: $moduleData['namespace'],
            providers: $moduleData['providers'] ?? [],
            aliases: $moduleData['aliases'] ?? [],
        );
    }
}
