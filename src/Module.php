<?php

namespace Laraneat\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Laraneat\Modules\Exceptions\ComposerException;
use Laraneat\Modules\Support\Composer;
use Laraneat\Modules\Support\ComposerJsonFile;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Symfony\Component\Console\Output\OutputInterface;

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
        string $packageName,
        ?string $name,
        string $path,
        string $namespace,
        array $providers,
        array $aliases,
    ) {
        $this->app = $app;
        $this->filesystem = $app['files'] ?: new Filesystem();
        $this->modulesRepository = $modulesRepository;
        $this->packageName = trim($packageName);
        $this->name = trim($name ?? "") ?: Str::afterLast($this->packageName, '/');
        $this->path = rtrim($path, '/');
        $this->namespace = trim($namespace, '\\');
        $this->providers = $providers;
        $this->aliases = $aliases;
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
     *
     * @throws ComposerException
     */
    public function delete(\Closure|OutputInterface $output = null): bool
    {
        $status = $this->filesystem->deleteDirectory($this->getPath());
        $this->modulesRepository->pruneModulesManifest();

        $composerClass = Composer::class;
        $composer = $this->app[$composerClass];
        if (!($composer instanceof Composer)) {
            throw ComposerException::make("$composerClass not registered in your app.");
        }

        if (!$composer->removePackages([$this->getPackageName()], false, $output)) {
            throw ComposerException::make("Failed to remove package with composer.");
        }

        return $status;
    }

    /**
     * Get sub path.
     */
    public function subPath(string $subPath): string
    {
        return $this->getPath() . '/' . GeneratorHelper::normalizePath($subPath);
    }

    /**
     * Get sub namespace.
     */
    public function subNamespace(string $subNamespace): string
    {
        return $this->getNamespace() . '\\' . GeneratorHelper::normalizeNamespace($subNamespace);
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
     *     path: string,
     *     packageName: string,
     *     name: string,
     *     namespace: string,
     *     providers: array<int, class-string>,
     *     aliases: array<string, class-string>
     * }
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'packageName' => $this->packageName,
            'name' => $this->name,
            'namespace' => $this->namespace,
            'providers' => $this->providers,
            'aliases' => $this->aliases,
        ];
    }

    /**
     * Save changes to composer.json
     *
     * @return $this
     *
     * @throws \Exception
     */
    protected function save(): static
    {
        ComposerJsonFile::create($this->path . '/composer.json')
            ->set('extra.laravel.providers', $this->providers)
            ->set('extra.laravel.aliases', $this->aliases)
            ->save();

        $this->modulesRepository->pruneModulesManifest();

        return $this;
    }
}
