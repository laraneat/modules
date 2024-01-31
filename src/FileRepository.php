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
     * Default modules path.
     *
     * @var string|null
     */
    protected ?string $defaultPath;

    /**
     * The scanned paths.
     *
     * @var string[]
     */
    protected array $paths = [];

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
     * @var ActivatorInterface
     */
    private ActivatorInterface $activator;

    /**
     * @var CacheManager
     */
    private CacheManager $cache;

    /**
     * @var array|null
     */
    private ?array $cachedModules = null;

    public function __construct(Application $app, ?string $defaultPath = null)
    {
        $this->app = $app;
        $this->defaultPath = $defaultPath ? rtrim($defaultPath, '/') : null;
        $this->url = $app['url'];
        $this->config = $app['config'];
        $this->filesystem = $app['files'];
        $this->activator = $app[ActivatorInterface::class];
        $this->cache = $app['cache'];
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
     * @inheritDoc
     * @see RepositoryInterface::boot()
     */
    public function boot(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->boot();
        }
    }

    /**
     * Get modules path.
     */
    public function getDefaultPath(): string
    {
        return $this->defaultPath ?: rtrim($this->config('generator.path', base_path('app/Modules')), '/');
    }

    /**
     * Get all additional paths.
     *
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Add other module location.
     */
    public function addLocation(string $path): static
    {
        $this->paths[] = $path;
        $this->flushCache();

        return $this;
    }

    /**
     * Get scanned modules paths.
     *
     * @return string[]
     */
    public function getScanPaths(): array
    {
        $paths = $this->paths;

        $paths[] = $this->getDefaultPath();

        if ($this->config('scan.enabled')) {
            $paths = array_merge($paths, $this->config('scan.paths'));
        }

        return array_map(static function ($path) {
            return Str::endsWith($path, '/*') ? $path : Str::finish($path, '/*');
        }, $paths);
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
     * Get all modules.
     *
     * @return array<string, Module>
     */
    public function all(): array
    {
        if (is_array($this->cachedModules)) {
            return $this->cachedModules;
        }

        if (!$this->config('cache.enabled')) {
            return $this->cachedModules = $this->scan();
        }

        return $this->cachedModules = $this->getCached();
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
        return $this->formatCached($this->getRawCached());
    }

    /**
     * Get raw cache of modules.
     */
    protected function getRawCached(): array
    {
        return $this->cache->remember($this->config('cache.key'), $this->config('cache.lifetime'), function () {
            return $this->toCollection()->toArray();
        });
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
     * Get & scan all modules.
     *
     * @return array<string, Module>
     */
    public function scan(): array
    {
        $paths = $this->getScanPaths();

        $modules = [];

        foreach ($paths as $path) {
            $manifests = $this->getFilesystem()->glob("$path/module.json");

            is_array($manifests) || $manifests = [];

            foreach ($manifests as $manifest) {
                $json = Json::make($manifest, $this->filesystem);
                $path = dirname($manifest);
                $name = (string) $json->get('name');
                $moduleKey = $this->getModuleKey($name);
                $namespace = (string) $json->get('namespace', '');

                $modules[$moduleKey] = $this->createModule(
                    $this->app,
                    $name,
                    $path,
                    $namespace,
                    ['module.json' => $json]
                );
            }
        }

        return $modules;
    }

    /**
     * Creates a new Module instance
     */
    protected function createModule(
        Application $app,
        string $name,
        string $path,
        string $namespace,
        array $moduleJson = []
    ): Module {
        return new Module($app, $name, $path, $namespace, $moduleJson);
    }

    /**
     * Get all modules as collection instance.
     *
     * @return Collection<string, Module>
     */
    public function toCollection(): Collection
    {
        return new Collection($this->scan());
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
     * Format the cached data as array of modules.
     *
     * @return array<string, Module>
     */
    protected function formatCached(array $cached): array
    {
        $modules = [];

        /**
         * @var string $moduleKey
         * @var array{name: string, path: string, namespace: string, module_json: array} $moduleArray
         */
        foreach ($cached as $moduleKey => $moduleArray) {
            $moduleJson = array_map(
                fn ($json) => Json::make($json['path'], $this->filesystem, $json['attributes']),
                $moduleArray['module_json'] ?? []
            );
            $modules[$moduleKey] = $this->createModule(
                $this->app,
                $moduleArray['name'],
                $moduleArray['path'],
                $moduleArray['namespace'],
                $moduleJson
            );
        }

        return $modules;
    }

    /**
     * Get all modules as laravel collection instance.
     *
     * @param bool $status
     *
     * @return Collection<string, Module>
     */
    public function collections(bool $status = true): Collection
    {
        return new Collection($this->getByStatus($status));
    }

    /**
     * @inheritDoc
     * @see RepositoryInterface::assetPath()
     */
    public function assetPath(string $moduleName): string
    {
        return rtrim($this->config('paths.assets'), '/') . '/' . $moduleName;
    }

    /**
     * @inheritDoc
     * @see RepositoryInterface::config()
     */
    public function config(string $key, $default = null)
    {
        return $this->config->get('modules.' . $key, $default);
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
     * @inheritDoc
     * @see RepositoryInterface::isEnabled()
     */
    public function isEnabled(string $moduleName): bool
    {
        return $this->findOrFail($moduleName)->isEnabled();
    }

    /**
     * @inheritDoc
     * @see RepositoryInterface::isDisabled()
     */
    public function isDisabled(string $moduleName): bool
    {
        return !$this->isEnabled($moduleName);
    }

    /**
     * Enabling a specific module.
     *
     * @param string $moduleName
     *
     * @return void
     * @throws ModuleNotFoundException
     */
    public function enable(string $moduleName): void
    {
        $this->findOrFail($moduleName)->enable();
    }

    /**
     * Disabling a specific module.
     *
     * @param string $moduleName
     *
     * @return void
     * @throws ModuleNotFoundException
     */
    public function disable(string $moduleName): void
    {
        $this->findOrFail($moduleName)->disable();
    }

    /**
     * @inheritDoc
     * @see RepositoryInterface::delete()
     */
    public function delete(string $moduleName): bool
    {
        return $this->findOrFail($moduleName)->delete();
    }

    /**
     * Update dependencies for the specified module.
     */
    public function update(string $moduleName): void
    {
        (new Updater($this))->update($moduleName);
    }

    /**
     * Install the specified module.
     */
    public function install(string $name, ?string $version = 'dev-master', ?string $type = 'composer', bool $subtree = false): Process
    {
        $installer = new Installer($name, $version, $type, $subtree);

        return $installer->run();
    }

    /**
     * Flush modules cache
     */
    public function flushCache(): void
    {
        $this->cachedModules = null;
        $this->cache->forget(config('modules.cache.key'));
        $this->activator->flushCache();
    }
}
