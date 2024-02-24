<?php

namespace Laraneat\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Laraneat\Modules\Exceptions\CannotModifyVendorModule;
use Symfony\Component\Process\Process;

class Module implements Arrayable
{
    use Macroable;

    /**
     * The laravel application instance.
     */
    protected Application $app;

    /**
     * The laravel filesystem instance.
     */
    protected Filesystem $filesystem;

    /**
     * The laraneat modules repository instance.
     */
    protected ModulesRepository $modulesRepository;

    /**
     * Determines the module is a vendor package.
     */
    protected bool $isVendor;

    /**
     * The module package name.
     */
    protected string $packageName;

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
     * Module providers
     *
     * @var array<int, class-string>
     */
    protected array $providers = [];

    /**
     * Module aliases
     *
     * @var array<string, class-string>
     */
    protected array $aliases = [];

    /**
     * @param Application $app The laravel application instance.
     * @param ModulesRepository $modulesRepository The laraneat modules repository instance.
     * @param bool $isVendor Determines whether the module is a vendor package.
     * @param string $packageName The module package name.
     * @param string|null $name The module name.
     * @param string $path The module path.
     * @param string $namespace The module namespace.
     * @param array<int, class-string> $providers Module providers
     * @param array<string, class-string> $aliases Module aliases
     */
    public function __construct(
        Application $app,
        ModulesRepository $modulesRepository,
        bool $isVendor,
        string $packageName,
        ?string $name,
        string $path,
        string $namespace,
        array $providers,
        array $aliases,
    ) {
        $this->app = $app;
        $this->filesystem = $app['files'];
        $this->modulesRepository = $modulesRepository;
        $this->isVendor = $isVendor;
        $this->packageName = trim($packageName);
        $this->name = $name ? trim($name) : Str::afterLast($this->packageName, '/');
        $this->path = rtrim($path, '/');
        $this->namespace = trim($namespace, '\\');
        $this->providers = $providers;
        $this->aliases = $aliases;
    }

    /**
     * Determines the module is a vendor package.
     */
    public function isVendor(): bool
    {
        return $this->isVendor;
    }

    /**
     * Get package name.
     */
    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get name in studly case.
     */
    public function getStudlyName(): string
    {
        return Str::studly($this->name);
    }

    /**
     * Get name in kebab case.
     */
    public function getKebabName(): string
    {
        return Str::kebab(str_replace('_', '-', $this->name));
    }

    /**
     * Get name in snake case.
     */
    public function getSnakeName(): string
    {
        return Str::snake(str_replace('-', '_', $this->name));
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
     * Get module providers.
     *
     * @return array<int, class-string>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get module aliases.
     *
     * @return array<string, class-string>
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Set module providers.
     *
     * @param array<int, class-string> $providers
     *
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws CannotModifyVendorModule
     */
    public function setProviders(array $providers): static
    {
        $this->providers = $providers;

        return $this->save();
    }

    /**
     * Set module aliases.
     *
     * @param array<string, class-string> $aliases
     *
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws CannotModifyVendorModule
     */
    public function setAliases(array $aliases): static
    {
        $this->aliases = $aliases;

        return $this->save();
    }

    /**
     * Set module providers.
     *
     * @param class-string $providerClass
     *
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws CannotModifyVendorModule
     */
    public function addProvider(string $providerClass): static
    {
        if (! in_array($providerClass, $this->providers)) {
            $this->providers[] = $providerClass;

            return $this->save();
        }

        return $this;
    }

    /**
     * Set module providers.
     *
     * @param string $alias
     * @param class-string $class
     *
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws CannotModifyVendorModule
     */
    public function addAlias(string $alias, string $class): static
    {
        if (! isset($this->aliases[$alias]) || $this->aliases[$alias] !== $class) {
            $this->aliases[$alias] = $class;

            return $this->save();
        }

        return $this;
    }

    /**
     * Delete the current module.
     */
    public function delete(): bool
    {
        $this->fireEvent('deleting');

        if ($this->isVendor()) {
            $this->modulesRepository->pruneVendorModulesManifest();
            $status = Process::fromShellCommandline(sprintf(
                'cd %s && composer remove %s',
                base_path(),
                $this->getPackageName()
            ))->run() === 0;
        } else {
            $status = $this->filesystem->deleteDirectory($this->getPath());
            $this->modulesRepository->pruneAppModulesManifest();
        }

        $this->fireEvent('deleted');

        return $status;
    }

    /**
     * Get sub path.
     */
    public function subPath(string $subPath): string
    {
        $subPath = trim(str_replace('\\', '/', $subPath), '/');

        return $this->getPath() . '/' . $subPath;
    }

    /**
     * Get sub namespace.
     */
    public function subNamespace(string $subNamespace): string
    {
        $subNamespace = trim(str_replace('/', '\\', $subNamespace), '\\');

        return $this->getNamespace() . '\\' . $subNamespace;
    }

    /**
     * Get migration paths.
     *
     * @return array<int, string>
     */
    public function getMigrationPaths(): array
    {
        /** @var Migrator|null $migrator */
        $migrator = $this->app['migrator'] ?? null;

        if ($migrator === null) {
            return [];
        }

        return collect($migrator->paths())
            ->filter(fn (string $path) => Str::startsWith($path, $this->getPath()))
            ->values()
            ->toArray();
    }

    /**
     * Handle call __toString.
     */
    public function __toString()
    {
        return $this->getPackageName();
    }

    /**
     * @return array{
     *     isVendor: bool,
     *     packageName: string,
     *     name: string,
     *     path: string,
     *     namespace: string,
     *     providers: array<int, class-string>,
     *     aliases: array<string, class-string>
     * }
     */
    public function toArray(): array
    {
        return [
            'isVendor' => $this->isVendor,
            'packageName' => $this->packageName,
            'name' => $this->name,
            'path' => $this->path,
            'namespace' => $this->namespace,
            'providers' => $this->providers,
            'aliases' => $this->aliases,
        ];
    }

    /**
     * Register the module event.
     */
    protected function fireEvent(string $event): void
    {
        $this->app['events']->dispatch(sprintf('modules.%s.' . $event, $this->getPackageName()), [$this]);
    }

    /**
     * Save changes to composer.json
     *
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws CannotModifyVendorModule
     */
    protected function save(): static
    {
        if ($this->isVendor()) {
            throw CannotModifyVendorModule::make($this->getPackageName());
        }

        /** @var Filesystem $filesystem */
        $filesystem = $this->app['files'];
        $composerJsonPath = $this->path . '/composer.json';

        $composerJsonContent = json_decode($filesystem->get($composerJsonPath), true);
        Arr::set($composerJsonContent, 'extra.laraneat.module.providers', $this->providers);
        Arr::set($composerJsonContent, 'extra.laraneat.module.aliases', $this->aliases);

        $filesystem->put($composerJsonPath, json_encode($composerJsonContent, JSON_PRETTY_PRINT));

        $this->modulesRepository->pruneAppModulesManifest();

        return $this;
    }
}
