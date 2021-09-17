<?php

namespace Laraneat\Modules;

use Countable;
use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Exceptions\InvalidAssetPath;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Process\Installer;
use Laraneat\Modules\Process\Updater;
use Symfony\Component\Process\Process;

abstract class FileRepository implements RepositoryInterface, Countable
{
    use Macroable;

    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application|\Laravel\Lumen\Application
     */
    protected $app;

    /**
     * The module path.
     *
     * @var string|null
     */
    protected ?string $path;

    /**
     * The scanned paths.
     *
     * @var string[]
     */
    protected array $paths = [];

    /**
     * @var string|null
     */
    protected ?string $stubPath = null;

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
    private Filesystem $files;

    /**
     * @var CacheManager
     */
    private CacheManager $cache;

    /**
     * @param Container $app
     * @param string|null $path
     */
    public function __construct(Container $app, ?string $path = null)
    {
        $this->app = $app;
        $this->path = $path;
        $this->url = $app['url'];
        $this->config = $app['config'];
        $this->files = $app['files'];
        $this->cache = $app['cache'];
    }

    /**
     * Creates a new Module instance
     *
     * @param Container $app
     * @param string $name
     * @param string $path
     *
     * @return Module
     */
    abstract protected function createModule(...$args);

    /**
     * @inheritDoc
     * @see RepositoryInterface::register()
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
     * @inheritDoc
     * @see RepositoryInterface::getPath()
     */
    public function getPath(): string
    {
        return $this->path ?: $this->config('paths.modules', base_path('app/Modules'));
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
     *
     * @param string $path
     *
     * @return $this
     */
    public function addLocation(string $path)
    {
        $this->paths[] = $path;

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

        $paths[] = $this->getPath();

        if ($this->config('scan.enabled')) {
            $paths = array_merge($paths, $this->config('scan.paths'));
        }

        return array_map(static function ($path) {
            return Str::endsWith($path, '/*') ? $path : Str::finish($path, '/*');
        }, $paths);
    }

    /**
     * Get stub path.
     *
     * @return string|null
     */
    public function getStubPath(): ?string
    {
        if ($this->stubPath !== null) {
            return $this->stubPath;
        }

        if ($this->config('stubs.enabled') === true) {
            return $this->config('stubs.path');
        }

        return $this->stubPath;
    }

    /**
     * Set stub path.
     *
     * @param string $stubPath
     *
     * @return $this
     */
    public function setStubPath(string $stubPath)
    {
        $this->stubPath = $stubPath;

        return $this;
    }

    /**
     * Get module path for a specific module.
     *
     * @param string $moduleName
     *
     * @return string
     */
    public function getModulePath(string $moduleName): string
    {
        try {
            return $this->findOrFail($moduleName)->getPath() . '/';
        } catch (ModuleNotFoundException $e) {
            return $this->getPath() . '/' . Str::studly($moduleName) . '/';
        }
    }

    /**
     * Get all modules.
     *
     * @return array<string, Module>
     */
    public function all(): array
    {
        if (!$this->config('cache.enabled')) {
            return $this->scan();
        }

        return $this->formatCached($this->getCached());
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
        return $this->cache->remember($this->config('cache.key'), $this->config('cache.lifetime'), function () {
            return $this->toCollection()->toArray();
        });
    }

    /**
     * Get all ordered modules.
     *
     * @param string $direction
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
     * @param bool $status
     *
     * @return array<string, Module>
     */
    public function getByStatus(bool $status): array
    {
        $modules = [];

        /** @var Module $module */
        foreach ($this->all() as $name => $module) {
            if ($module->isStatus($status)) {
                $modules[$name] = $module;
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

        foreach ($paths as $key => $path) {
            $manifests = $this->getFiles()->glob("{$path}/module.json");

            is_array($manifests) || $manifests = [];

            foreach ($manifests as $manifest) {
                $name = Json::make($manifest)->get('name');

                $modules[$name] = $this->createModule($this->app, $name, dirname($manifest));
            }
        }

        return $modules;
    }

    /**
     * Get all modules as collection instance.
     *
     * @return Collection<Module>
     */
    public function toCollection(): Collection
    {
        return new Collection($this->scan());
    }

    /**
     * Determine whether the given module exist.
     *
     * @param string $moduleName
     *
     * @return bool
     */
    public function has(string $moduleName): bool
    {
        return array_key_exists($moduleName, $this->all());
    }

    /**
     * Get count from all modules.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * @inheritDoc
     * @see RepositoryInterface::find()
     */
    public function find(string $moduleName): ?Module
    {
        foreach ($this->all() as $module) {
            if ($module->getLowerName() === strtolower($moduleName)) {
                return $module;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     * @see RepositoryInterface::findByAlias()
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
     * @param string $moduleName
     *
     * @return Module
     * @throws ModuleNotFoundException
     */
    public function findOrFail(string $moduleName): Module
    {
        $module = $this->find($moduleName);

        if ($module !== null) {
            return $module;
        }

        throw new ModuleNotFoundException("Module [{$moduleName}] does not exist!");
    }

    /**
     * @inheritDoc
     * @see RepositoryInterface::findRequirements()
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
     * @param array $cached
     *
     * @return array
     */
    protected function formatCached(array $cached): array
    {
        $modules = [];

        foreach ($cached as $name => $module) {
            $path = $module['path'];

            $modules[$name] = $this->createModule($this->app, $name, $path);
        }

        return $modules;
    }

    /**
     * Get all modules as laravel collection instance.
     *
     * @param bool $status
     *
     * @return Collection<Module>
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
        return $this->config('paths.assets') . '/' . $moduleName;
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
     *
     * @return string
     */
    public function getUsedStoragePath(): string
    {
        $directory = storage_path('app/modules');
        if ($this->getFiles()->exists($directory) === false) {
            $this->getFiles()->makeDirectory($directory, 0777, true);
        }

        $path = storage_path('app/modules/modules.used');
        if (!$this->getFiles()->exists($path)) {
            $this->getFiles()->put($path, '');
        }

        return $path;
    }

    /**
     * Set module used for cli session.
     *
     * @param string $moduleName
     *
     * @throws ModuleNotFoundException
     */
    public function setUsed(string $moduleName): void
    {
        $module = $this->findOrFail($moduleName);

        $this->getFiles()->put($this->getUsedStoragePath(), $module);
    }

    /**
     * Forget the module used for cli session.
     */
    public function forgetUsed(): void
    {
        if ($this->getFiles()->exists($this->getUsedStoragePath())) {
            $this->getFiles()->delete($this->getUsedStoragePath());
        }
    }

    /**
     * Get module used for cli session.
     * @return string
     * @throws ModuleNotFoundException
     */
    public function getUsedNow(): string
    {
        return $this->findOrFail($this->getFiles()->get($this->getUsedStoragePath()));
    }

    /**
     * Get laravel filesystem instance.
     *
     * @return Filesystem
     */
    public function getFiles(): Filesystem
    {
        return $this->files;
    }

    /**
     * Get module assets path.
     *
     * @return string
     */
    public function getAssetsPath(): string
    {
        return $this->config('paths.assets');
    }

    /**
     * Get asset url from a specific module.
     *
     * @param string $asset
     *
     * @return string
     * @throws InvalidAssetPath
     */
    public function asset(string $asset): string
    {
        if (Str::contains($asset, ':') === false) {
            throw InvalidAssetPath::missingModuleName($asset);
        }
        list($name, $url) = explode(':', $asset);

        $baseUrl = str_replace(public_path() . DIRECTORY_SEPARATOR, '', $this->getAssetsPath());

        $url = $this->url->asset($baseUrl . "/{$name}/" . $url);

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
     *
     * @param string $module
     *
     * @return void
     */
    public function update(string $module): void
    {
        (new Updater($this))->update($module);
    }

    /**
     * Install the specified module.
     *
     * @param string $name
     * @param string|null $version
     * @param string|null $type
     * @param bool|null $subtree
     *
     * @return Process
     */
    public function install(string $name, ?string $version = 'dev-master', ?string $type = 'composer', bool $subtree = false): Process
    {
        $installer = new Installer($name, $version, $type, $subtree);

        return $installer->run();
    }
}
