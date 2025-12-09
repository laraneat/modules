<?php

namespace Laraneat\Modules;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

/**
 * @implements Arrayable<string, mixed>
 */
class Module implements Arrayable
{
    use Macroable;

    protected string $packageName;
    protected string $name;
    protected string $path;
    protected string $namespace;

    /**
     * @param string $packageName The module package name.
     * @param string $name The module name.
     * @param string $path The module path.
     * @param string $namespace The module namespace.
     * @param array<int, class-string> $providers Module providers
     * @param array<string, class-string> $aliases Module aliases
     */
    public function __construct(
        string $packageName,
        string $name,
        string $path,
        string $namespace,
        protected array $providers = [],
        protected array $aliases = [],
    ) {
        $this->packageName = trim($packageName);
        $this->name = trim($name) ?: Str::afterLast($this->packageName, '/');
        $this->path = rtrim($path, '/');
        $this->namespace = trim($namespace, '\\');
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
     * Handle call __toString.
     */
    public function __toString(): string
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
}
