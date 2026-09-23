<?php

declare(strict_types=1);

namespace Laraneat\Modules;

use Illuminate\Database\Seeder;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Manifest\ManifestBuilder;
use Laraneat\Modules\Manifest\ManifestCache;
use ReflectionClass;

/**
 * @phpstan-import-type Manifest from ManifestBuilder
 */
final class ModuleRepository
{
    /**
     * Manifests are immutable, so every application of the process shares them:
     * the test suite builds the manifest once instead of once per test.
     *
     * @var array<string, Manifest>
     */
    private static array $manifests = [];

    /**
     * @var array<string, Module>|null
     */
    private ?array $modules = null;

    public function __construct(
        private readonly ManifestBuilder $builder,
        private readonly ManifestCache $cache,
    ) {}

    /**
     * @return array<string, Module>
     */
    public function all(): array
    {
        return $this->modules ??= array_map(static fn (array $module): Module => new Module(
            name: basename($module['path']),
            package: $module['package'],
            namespace: $module['namespace'],
            path: $module['path'],
            sourcePath: $module['src'] === '' ? $module['path'] : $module['path'].'/'.$module['src'],
        ), $this->manifest());
    }

    public function find(string $name): ?Module
    {
        return $this->all()[$name] ?? null;
    }

    /**
     * @throws ModuleNotFound
     */
    public function get(string $name): Module
    {
        return $this->find($name) ?? throw ModuleNotFound::named($name);
    }

    /**
     * Seeders of all modules, from "database/seeders" and the given subdirectories of it
     * ("" is "database/seeders" itself). They run in the order of their "_N" class name
     * suffix; seeders without a suffix run last. Equal suffixes run by class name.
     *
     * @return list<class-string<Seeder>>
     */
    public function seeders(string ...$directories): array
    {
        $directories = $directories === [] ? [''] : array_unique(array_map(static fn (string $directory): string => trim($directory, '/\\'), $directories));
        $seeders = [];

        foreach ($this->manifest() as $module) {
            foreach ($directories as $directory) {
                foreach ($module['seeders'][$directory] ?? [] as $class) {
                    if (is_subclass_of($class, Seeder::class) && ! (new ReflectionClass($class))->isAbstract()) {
                        $seeders[] = $class;
                    }
                }
            }
        }

        usort($seeders, static fn (string $a, string $b): int => [self::order($a), $a] <=> [self::order($b), $b]);

        return $seeders;
    }

    /**
     * @internal
     *
     * @return Manifest
     */
    public function manifest(): array
    {
        return self::$manifests[$this->key()] ??= $this->cache->exists() ? $this->cache->read() : $this->builder->build();
    }

    /**
     * @internal
     */
    public function modulesPath(): string
    {
        return $this->builder->modulesPath();
    }

    /**
     * @internal
     */
    public function cachePath(): string
    {
        return $this->cache->path();
    }

    /**
     * @internal
     */
    public function isCached(): bool
    {
        return $this->cache->exists();
    }

    /**
     * @internal
     */
    public function cache(): void
    {
        $this->cache->write($this->builder->build());
        $this->forget();
    }

    /**
     * @internal
     */
    public function clearCache(): void
    {
        $this->cache->delete();
        $this->forget();
    }

    /**
     * Pick up created or deleted modules; the cache file is rebuilt only if it exists.
     *
     * @internal
     */
    public function refresh(): void
    {
        $this->forget();

        if ($this->cache->exists()) {
            $this->cache();
        }
    }

    /**
     * Whether the cache file differs from the modules on disk.
     *
     * @internal
     */
    public function isCacheStale(): bool
    {
        return $this->cache->exists() && $this->cache->read() !== $this->builder->build();
    }

    /**
     * @internal
     */
    public static function flushState(): void
    {
        self::$manifests = [];
    }

    private function forget(): void
    {
        unset(self::$manifests[$this->key()]);
        $this->modules = null;
    }

    private function key(): string
    {
        return $this->cache->path()."\0".$this->builder->key();
    }

    private static function order(string $class): int
    {
        $basename = class_basename($class);
        $suffix = substr((string) strrchr($basename, '_'), 1);

        return ctype_digit($suffix) ? (int) $suffix : PHP_INT_MAX;
    }
}
