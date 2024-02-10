<?php

namespace Laraneat\Modules;

use Countable;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Exceptions\InvalidAssetPath;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Process\Installer;
use Laraneat\Modules\Process\Updater;
use Symfony\Component\Process\Process;

class FileRepository implements RepositoryInterface, Countable
{
    use Macroable;

    /**
     * The laravel application instance.
     */
    protected Application $app;

    /**
     * @var UrlGenerator
     */
    private UrlGenerator $url;

    /**
     * @var ConfigRepository
     */
    private ConfigRepository $config;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var CacheManager
     */
    private CacheManager $cache;

    /**
     * @var ActivatorInterface
     */
    private ActivatorInterface $activator;

    /**
     * The scanned paths.
     *
     * @var string[]
     */
    protected array $scanPaths = [];

    /**
     * @var array|null
     */
    private ?array $cachedModules = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->url = $app['url'];
        $this->config = $app['config'];
        $this->filesystem = $app['files'];
        $this->cache = $app['cache'];
        $this->activator = $app[ActivatorInterface::class];

        $scanPaths = $this->config('paths.modules', []);
        $this->scanPaths = is_array($scanPaths)
            ? array_map([$this, 'normalizeScanPath'], $scanPaths)
            : [];
    }

    /**
     * Register the modules.
     */
    public function register(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->register();
        }
    }

    /**
     * Boot the modules.
     */
    public function boot(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->boot();
        }
    }

    /**
     * Get all modules.
     *
     * @return array<string, Module>
     */
    public function all(): array
    {
        if (is_array($this->cachedModules)) {
            return $this->cachedModules;
        }

        return $this->cachedModules = $this->config('cache.enabled', false)
            ? $this->getCached()
            : $this->scan();
    }

    /**
     * Get list of enabled modules.
     *
     * @return array<string, Module>
     */
    public function allEnabled(): array
    {
        return $this->getByStatus(true);
    }

    /**
     * Get list of disabled modules.
     *
     * @return array<string, Module>
     */
    public function allDisabled(): array
    {
        return $this->getByStatus(false);
    }

    /**
     * Get cached modules.
     *
     * @return array<string, Module>
     */
    public function getCached(): array
    {
        return $this->parseModulesFromArray($this->getRawCached());
    }

    /**
     * Get raw cache of modules.
     *
     * @return array<string, array{name: string, path: string, namespace: string, jsons: array<string, array{path: string, attributes: array}>}>
     */
    protected function getRawCached(): array
    {
        return $this->cache->remember(
            $this->config('cache.key'),
            $this->config('cache.lifetime'),
            fn () => $this->scanRaw()
        );
    }

    /**
     * Get all ordered modules.
     *
     * @return array<string, Module>
     */
    public function getOrdered(string $direction = 'asc'): array
    {
        $modules = $this->allEnabled();

        uasort($modules, static function (Module $a, Module $b) use ($direction) {
            if ($a->getPriority() === $b->getPriority()) {
                return 0;
            }

            if ($direction === 'desc') {
                return $a->getPriority() < $b->getPriority() ? 1 : -1;
            }

            return $a->getPriority() > $b->getPriority() ? 1 : -1;
        });

        return $modules;
    }

    /**
     * Get modules by status.
     *
     * @return array<string, Module>
     */
    public function getByStatus(bool $status): array
    {
        $modules = [];

        foreach ($this->all() as $moduleKey => $module) {
            if ($module->isStatus($status)) {
                $modules[$moduleKey] = $module;
            }
        }

        return $modules;
    }

    /**
     * Scan and get all modules.
     *
     * @return array<string, Module>
     */
    public function scan(): array
    {
        return $this->parseModulesFromArray($this->scanRaw());
    }

    /**
     * Scan and get all modules as raw array.
     *
     * @return array<string, array{name: string, path: string, namespace: string, jsons: array<string, array{path: string, attributes: array}>}>
     */
    public function scanRaw(): array
    {
        $paths = $this->getScanPaths();

        $modules = [];

        foreach ($paths as $path) {
            $manifests = $this->getFilesystem()->glob("$path/module.json");

            if (!is_array($manifests)) {
                continue;
            }

            foreach ($manifests as $manifest) {
                $moduleJson = Json::make($manifest, $this->filesystem);
                $path = dirname($manifest);
                $name = (string) $moduleJson->get('name');
                $moduleKey = $this->getModuleKey($name);
                $namespace = (string) $moduleJson->get('namespace', '');

                $modules[$moduleKey] = [
                    'name' => $name,
                    'path' => $path,
                    'namespace' => $namespace,
                    'jsons' => [
                        'module.json' => $moduleJson->toArray()
                    ],
                ];
            }
        }

        return $modules;
    }

    /**
     * Determine whether the given module exist.
     */
    public function has(string $moduleName): bool
    {
        $moduleKey = $this->getModuleKey($moduleName);
        return array_key_exists($moduleKey, $this->all());
    }

    /**
     * Get count from all modules.
     */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * Find a specific module.
     */
    public function find(string $moduleName): ?Module
    {
        $allModules = $this->all();
        $moduleKey = $this->getModuleKey($moduleName);

        return $allModules[$moduleKey] ?? null;
    }

    /**
     * Find a specific module by its alias.
     */
    public function findByAlias(string $alias): ?Module
    {
        foreach ($this->all() as $module) {
            if ($module->getAlias() === $alias) {
                return $module;
            }
        }

        return null;
    }

    /**
     * Find a specific module, if there return that, otherwise throw exception.
     *
     * @throws ModuleNotFoundException
     */
    public function findOrFail(string $moduleName): Module
    {
        $module = $this->find($moduleName);

        if ($module !== null) {
            return $module;
        }

        throw new ModuleNotFoundException("Module [$moduleName] does not exist!");
    }

    /**
     * Find all modules that are required by a module. If the module cannot be found, throw an exception.
     *
     * @return array<int, Module>
     * @throws ModuleNotFoundException
     */
    public function findRequirements(string $moduleName): array
    {
        $requirements = [];

        $module = $this->findOrFail($moduleName);

        foreach ($module->getRequires() as $requirementName) {
            $requirements[] = $this->findByAlias($requirementName);
        }

        return $requirements;
    }

    /**
     * Determine whether the given module is activated.
     *
     * @throws ModuleNotFoundException
     */
    public function isEnabled(string $moduleName): bool
    {
        return $this->findOrFail($moduleName)->isEnabled();
    }

    /**
     * Determine whether the given module is not activated.
     *
     * @throws ModuleNotFoundException
     */
    public function isDisabled(string $moduleName): bool
    {
        return !$this->isEnabled($moduleName);
    }

    /**
     * Enable a specific module.
     *
     * @throws ModuleNotFoundException
     */
    public function enable(string $moduleName): void
    {
        $this->findOrFail($moduleName)->enable();
    }

    /**
     * Disable a specific module.
     *
     * @throws ModuleNotFoundException
     */
    public function disable(string $moduleName): void
    {
        $this->findOrFail($moduleName)->disable();
    }

    /**
     * Delete a specific module.
     *
     * @throws ModuleNotFoundException
     */
    public function delete(string $moduleName): bool
    {
        return $this->findOrFail($moduleName)->delete();
    }

    /**
     * Update dependencies for the specified module.
     *
     * @throws ModuleNotFoundException
     */
    public function update(string $moduleName): void
    {
        (new Updater($this))->update($moduleName);
    }

    /**
     * Install the specified module.
     */
    public function install(string $name, ?string $version = 'latest', ?string $type = 'composer', bool $subtree = false): Process
    {
        $installer = new Installer($name, $version, $type, $subtree);

        return $installer->run();
    }

    /**
     * Get storage path for module used.
     */
    public function getUsedStoragePath(): string
    {
        $directory = storage_path('app/modules');
        if ($this->getFilesystem()->exists($directory) === false) {
            $this->getFilesystem()->makeDirectory($directory, 0777, true);
        }

        $path = storage_path('app/modules/modules.used');
        if (!$this->getFilesystem()->exists($path)) {
            $this->getFilesystem()->put($path, '');
        }

        return $path;
    }

    /**
     * Set module used for cli session.
     *
     * @throws ModuleNotFoundException
     */
    public function setUsed(string $moduleName): void
    {
        $module = $this->findOrFail($moduleName);

        $this->getFilesystem()->put($this->getUsedStoragePath(), $module);
    }

    /**
     * Forget the module used for cli session.
     */
    public function forgetUsed(): void
    {
        if ($this->getFilesystem()->exists($this->getUsedStoragePath())) {
            $this->getFilesystem()->delete($this->getUsedStoragePath());
        }
    }

    /**
     * Get module used for cli session.
     *
     * @throws ModuleNotFoundException|\Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getUsedNow(): Module
    {
        return $this->findOrFail($this->getFilesystem()->get($this->getUsedStoragePath()));
    }

    /**
     * Flush modules cache
     */
    public function flushCache(): void
    {
        $this->cachedModules = null;
        $this->cache->forget(config('modules.cache.key'));

        if (method_exists($this->activator, 'flushCache')) {
            $this->activator->flushCache();
        }
    }

    /**
     * Get all modules as collection instance.
     *
     * @return Collection<string, Module>
     */
    public function toCollection(): Collection
    {
        return new Collection($this->all());
    }

    /**
     * Get a module key by its name
     */
    public function getModuleKey(Module|string $module): string
    {
        return $module instanceof Module
            ? $module->getKey()
            : Str::snake($module, '-');
    }

    /**
     * Get path for a specific module.
     *
     * @throws ModuleNotFoundException
     */
    public function getModulePath(Module|string $module, ?string $extraPath = null): string
    {
        $modulePath = $module instanceof Module
            ? $module->getPath()
            : $this->findOrFail($module)->getPath();

        return $extraPath ? $modulePath . '/' . $extraPath : $modulePath;
    }

    /**
     * Get namespace for a specific module.
     *
     * @throws ModuleNotFoundException
     */
    public function getModuleNamespace(Module|string $module, ?string $extraNamespace = null): string
    {
        $moduleNamespace = $module instanceof Module
            ? $module->getNamespace()
            : $this->findOrFail($module)->getNamespace();

        return $extraNamespace ? $moduleNamespace . '\\' . $extraNamespace : $moduleNamespace;
    }

    /**
     * Get scanned modules paths.
     *
     * @return array<int, string>
     */
    public function getScanPaths(): array
    {
        return $this->scanPaths;
    }

    /**
     * Add scan path.
     */
    public function addScanPath(string $path): static
    {
        $normalizedScanPaths = $this->normalizeScanPath($path);

        if (in_array($normalizedScanPaths, $this->scanPaths)) {
            return $this;
        }

        $this->scanPaths[] = $normalizedScanPaths;
        $this->flushCache();

        return $this;
    }

    /**
     * Get laravel filesystem instance.
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Get modules assets path.
     */
    public function getAssetsPath(): string
    {
        return $this->config('paths.assets');
    }

    /**
     * Get asset path for a specific module.
     */
    public function assetPath(string $moduleName): string
    {
        return rtrim($this->config('paths.assets'), '/') . '/' . $moduleName;
    }

    /**
     * Get asset url from a specific module.
     *
     * @throws InvalidAssetPath
     */
    public function asset(string $asset): string
    {
        if (Str::contains($asset, ':') === false) {
            throw InvalidAssetPath::missingModuleName($asset);
        }
        [$name, $url] = explode(':', $asset);

        $baseUrl = str_replace(public_path() . DIRECTORY_SEPARATOR, '', $this->getAssetsPath());

        $url = $this->url->asset($baseUrl . "/$name/" . $url);

        return str_replace(['http://', 'https://'], '//', $url);
    }

    /**
     * Get a specific config data from a configuration file.
     */
    public function config(string $key, mixed $default = null): mixed
    {
        return $this->config->get('modules.' . $key, $default);
    }

    protected function normalizeScanPath(string $path): string
    {
        return Str::endsWith($path, '/*') ? $path : Str::finish($path, '/*');
    }

    /**
     * @param array<string, array{name: string, path: string, namespace: string, jsons: array<string, array{path: string, attributes: array}>}> $modulesArray
     *
     * @return array<string, Module>
     */
    protected function parseModulesFromArray(array $modulesArray): array
    {
        return array_map(fn ($moduleArray) => Module::makeFromArray($this->app, $moduleArray), $modulesArray);
    }
}
