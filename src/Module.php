<?php

namespace Laraneat\Modules;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\Facades\Modules;

class Module implements Arrayable
{
    use Macroable;

    /**
     * The laravel application instance.
     */
    protected Application $app;

    /**
     * The module name.
     */
    protected string $name;

    /**
     * The module path.
     */
    protected string $path;

    /**
     * The module namespace.
     */
    protected string $namespace;

    /**
     * @var array<string, Json> array of cached Json objects, keyed by filename
     */
    protected array $jsons = [];

    /**
     * The laravel filesystem instance.
     */
    private Filesystem $filesystem;

    /**
     * The activator instance.
     */
    private ActivatorInterface $activator;

    /**
     * @param array<string, Json> $jsons
     */
    public function __construct(
        Application $app,
        string $name,
        string $path,
        string $namespace,
        array $jsons = []
    ) {
        $this->app = $app;
        $this->name = trim($name);
        $this->path = rtrim($path, '/');
        $this->namespace = trim($namespace, '\\');
        $this->jsons = $jsons;
        $this->filesystem = $app['files'];
        $this->activator = $app[ActivatorInterface::class];
    }

    /**
     * Make the module from a plain array
     *
     * @param Application $app
     * @param array{name: string, path: string, namespace: string, jsons: array<string, array{path: string, attributes: array}>} $moduleArray
     *
     * @return Module
     *
     * @throws FileNotFoundException
     * @throws \JsonException
     */
    public static function makeFromArray(Application $app, array $moduleArray): Module
    {
        return new Module(
            $app,
            $moduleArray['name'],
            $moduleArray['path'],
            $moduleArray['namespace'],
            array_map(
                fn ($json) => Json::make($json['path'], $app['files'], $json['attributes']),
                $moduleArray['jsons']
            )
        );
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get key.
     */
    public function getKey(): string
    {
        return Str::snake($this->name, '-');
    }

    /**
     * Get name in studly case.
     */
    public function getStudlyName(): string
    {
        return Str::studly($this->name);
    }

    /**
     * Get name in snake case.
     */
    public function getSnakeName(): string
    {
        return Str::snake($this->name);
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->get('description');
    }

    /**
     * Get alias.
     */
    public function getAlias(): string
    {
        return $this->get('alias');
    }

    /**
     * Get priority.
     */
    public function getPriority(): string
    {
        return $this->get('priority');
    }

    /**
     * Get module requirements.
     */
    public function getRequires(): array
    {
        return $this->get('requires');
    }

    /**
     * Get path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get namespace.
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
        if ($this->isLoadFilesOnBoot()) {
            $this->registerFiles();
        }

        $this->fireEvent('boot');
    }

    /**
     * Get json contents from the cache, setting as needed.
     */
    public function json(?string $fileName = 'module.json'): Json
    {
        if ($fileName === null) {
            $fileName = 'module.json';
        }

        return Arr::get($this->jsons, $fileName, function () use ($fileName) {
            return $this->jsons[$fileName] = Json::make($this->getExtraPath($fileName), $this->filesystem);
        });
    }

    /**
     * Get a specific data from json file by given the key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->json()->get($key, $default);
    }

    /**
     * Get a specific data from composer.json file by given the key.
     */
    public function getComposerAttr(string $key, mixed $default = null): mixed
    {
        return $this->json('composer.json')->get($key, $default);
    }

    /**
     * Register the module.
     */
    public function register(): void
    {
        $this->registerAliases();
        $this->registerProviders();

        if ($this->isLoadFilesOnBoot() === false) {
            $this->registerFiles();
        }

        $this->fireEvent('register');
    }

    /**
     * Register the module event.
     */
    protected function fireEvent(string $event): void
    {
        $this->app['events']->dispatch(sprintf('modules.%s.' . $event, $this->getKey()), [$this]);
    }

    /**
     * Get the path to the cached *_module.php file.
     */
    public function getCachedServicesPath(): string
    {
        // This checks if we are running on a Laravel Vapor managed instance
        // and sets the path to a writable one (services path is not on a writable storage in Vapor).
        if (!is_null(env('VAPOR_MAINTENANCE_MODE'))) {
            /** @phpstan-ignore-next-line  */
            return Str::replaceLast('config.php', $this->getSnakeName() . '_module.php', $this->app->getCachedConfigPath());
        }

        /** @phpstan-ignore-next-line  */
        return Str::replaceLast('services.php', $this->getSnakeName() . '_module.php', $this->app->getCachedServicesPath());
    }

    /**
     * Register the service providers from this module.
     */
    public function registerProviders(): void
    {
        (new ProviderRepository($this->app, new Filesystem(), $this->getCachedServicesPath()))
            ->load($this->get('providers', []));
    }

    /**
     * Register the aliases from this module.
     */
    public function registerAliases(): void
    {
        $loader = AliasLoader::getInstance();
        foreach ($this->get('aliases', []) as $aliasName => $aliasClass) {
            $loader->alias($aliasName, $aliasClass);
        }
    }

    /**
     * Register the files from this module.
     */
    protected function registerFiles(): void
    {
        foreach ($this->get('files', []) as $fileName) {
            include $this->path . '/' . $fileName;
        }
    }

    /**
     * Handle call __toString.
     */
    public function __toString()
    {
        return $this->getStudlyName();
    }

    /**
     * Determine whether the given status same with the current module status.
     */
    public function isStatus(bool $status): bool
    {
        return $this->activator->hasStatus($this, $status);
    }

    /**
     * Determine whether the current module activated.
     */
    public function isEnabled(): bool
    {
        return $this->activator->hasStatus($this, true);
    }

    /**
     * Determine whether the current module not disabled.
     */
    public function isDisabled(): bool
    {
        return !$this->isEnabled();
    }

    /**
     * Set active state for current module.
     */
    public function setActive(bool $active): void
    {
        $this->activator->setActive($this, $active);
    }

    /**
     * Disable the current module.
     */
    public function disable(): void
    {
        $this->fireEvent('disabling');

        $this->activator->disable($this);
        $this->flushCache();

        $this->fireEvent('disabled');
    }

    /**
     * Enable the current module.
     */
    public function enable(): void
    {
        $this->fireEvent('enabling');

        $this->activator->enable($this);
        $this->flushCache();

        $this->fireEvent('enabled');
    }

    /**
     * Delete the current module.
     */
    public function delete(): bool
    {
        $this->fireEvent('deleting');

        $this->activator->delete($this);
        $status = $this->json()->getFilesystem()->deleteDirectory($this->getPath());
        $this->flushCache();

        $this->fireEvent('deleted');

        return $status;
    }

    /**
     * Get extra path.
     */
    public function getExtraPath(string $path): string
    {
        return $this->getPath() . '/' . ltrim($path, '/');
    }

    /**
     * Check if the module files can be loaded on boot.
     */
    protected function isLoadFilesOnBoot(): bool
    {
        return config('modules.register.files', 'register') === 'boot';
    }

    /**
     * Get the module as a plain array.
     *
     * @return array{name: string, path: string, namespace: string, module_json: array}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path,
            'namespace' => $this->namespace,
            'module_json' => array_map(static fn (Json $json) => $json->toArray(), $this->jsons)
        ];
    }

    /**
     * Flush modules cache.
     */
    protected function flushCache(): void
    {
        Modules::flushCache();
    }
}
