<?php

namespace Laraneat\Modules;

use Composer\Factory;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laraneat\Modules\Exceptions\ComposerException;
use Laraneat\Modules\Exceptions\DirectoryMustBePresentAndWritable;
use Laraneat\Modules\Exceptions\ModuleHasNoNamespace;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Support\Composer;
use Laraneat\Modules\Support\ComposerJsonFile;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Symfony\Component\Console\Output\OutputInterface;

class ModulesRepository implements Arrayable
{
    /**
     * The filesystem instance.
     */
    protected Filesystem $filesystem;

    /**
     * Paths for scanning modules.
     *
     * @var array<int, string>
     */
    protected array $scanPaths = [];

    /**
     * The loaded modules array.
     *
     * @var array<string, Module>
     */
    protected ?array $modules = null;

    /**
     * Create a new module manifest instance.
     */
    public function __construct(
        protected Application $app,
        protected string $modulesPath,
        protected ?string $modulesManifestPath = null,
    ) {
        $this->filesystem = $this->app['files'];
        $this->addScanPath($this->modulesPath);
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

        $this->modules = null;

        return $this;
    }

    /**
     * Build the modules manifest and write it to disk if cache enabled.
     *
     * @return array<string, array{ path: string, name: string, namespace: string, providers: class-string[], aliases: array<string, class-string> }>
     *
     * @throws ModuleHasNoNamespace
     * @throws ModuleHasNonUniquePackageName
     */
    public function buildModulesManifest(): array
    {
        $modulesManifest = [];

        foreach($this->scanPaths as $path) {
            $packagePaths = $this->filesystem->glob("$path/composer.json");

            foreach ($packagePaths as $packagePath) {
                $packagePath = GeneratorHelper::normalizePath($packagePath, true);
                $composerJsonFile = ComposerJsonFile::create($packagePath);
                $packageName = trim($composerJsonFile->get('name') ?? "");

                if (! $packageName) {
                    continue;
                }

                if (array_key_exists($packageName, $modulesManifest)) {
                    throw ModuleHasNonUniquePackageName::make($packageName);
                }

                $path = str_replace('\\', '/', dirname($packagePath));

                $moduleData = [
                    'path' => $path,
                    'name' => basename($path),
                    'namespace' => array_key_first($composerJsonFile->get('autoload.psr-4') ?? []),
                    'providers' => $composerJsonFile->get('extra.laravel.providers') ?? [],
                    'aliases' => $composerJsonFile->get('extra.laravel.aliases') ?? [],
                ];

                $modulesManifest[$packageName] = $this->validateModuleData($packageName, $moduleData);
            }
        }

        if ($this->modulesManifestPath) {
            $this->write($modulesManifest, $this->modulesManifestPath);
        }

        return $modulesManifest;
    }

    /**
     * Prune modules manifest
     */
    public function pruneModulesManifest(): bool
    {
        $this->modules = null;

        return $this->filesystem->delete($this->modulesManifestPath);
    }

    /**
     * Get discovered modules
     *
     * @return array<string, Module>
     *
     * @throws ModuleHasNoNamespace
     * @throws ModuleHasNonUniquePackageName
     */
    public function getModules(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        try {
            if ($this->modulesManifestPath && $this->filesystem->isFile($this->modulesManifestPath)) {
                return $this->modules = $this->makeModulesFromManifest(
                    $this->filesystem->getRequire($this->modulesManifestPath)
                );
            }
        } catch (FileNotFoundException) {
            //
        }

        return $this->modules = $this->makeModulesFromManifest($this->buildModulesManifest());
    }

    /**
     * Determine whether the given module exist by its package name.
     */
    public function has(string $modulePackageName): bool
    {
        return array_key_exists($modulePackageName, $this->getModules());
    }

    /**
     * Get count from all modules.
     */
    public function count(): int
    {
        return count($this->getModules());
    }

    /**
     * Find a specific module by its package name.
     */
    public function find(string $modulePackageName): ?Module
    {
        $modulePackageName = trim($modulePackageName);

        return $this->getModules()[$modulePackageName] ?? null;
    }

    /**
     * Find a specific module by its package name, if there return that, otherwise throw exception.
     *
     * @throws ModuleNotFound
     */
    public function findOrFail(string $modulePackageName): Module
    {
        $module = $this->find($modulePackageName);

        if ($module === null) {
            throw ModuleNotFound::make($modulePackageName);
        }

        return $module;
    }

    /**
     * Filter modules by name.
     *
     * @return array<string, Module>
     */
    public function filterByName(string $moduleName): array
    {
        $moduleName = trim($moduleName);
        $moduleKebabName = Str::kebab($moduleName);
        $moduleStudlyName = Str::studly($moduleName);
        $foundModules = [];

        foreach($this->getModules() as $modulePackageName => $module) {
            $modulePackageNameWithoutVendor = Str::kebab(Str::afterLast($modulePackageName, '/'));
            if ($module->getStudlyName() === $moduleStudlyName || $modulePackageNameWithoutVendor === $moduleKebabName) {
                $foundModules[$modulePackageName] = $module;
            }
        }

        return $foundModules;
    }

    /**
     * Find a specific module by its name, if there return that, otherwise throw exception.
     *
     * @return array<string, Module>
     *
     * @throws ModuleNotFound
     */
    public function filterByNameOrFail(string $moduleName): array
    {
        $modules = $this->filterByName($moduleName);

        if (! $modules) {
            throw ModuleNotFound::makeForName($moduleName);
        }

        return $modules;
    }

    /**
     * Delete a specific module by its package name.
     *
     * @throws ModuleNotFound
     * @throws ComposerException
     */
    public function delete(string $modulePackageName, \Closure|OutputInterface $output = null): bool
    {
        return $this->findOrFail($modulePackageName)->delete($output);
    }

    /**
     * @throws ModuleHasNoNamespace
     * @throws ModuleHasNonUniquePackageName
     * @throws ComposerException
     */
    public function syncWithComposer(\Closure|OutputInterface $output = null): void
    {
        $initialWorkingDir = getcwd();
        $appBasePath = $this->app->basePath();
        chdir($appBasePath);
        $composerJsonFile = ComposerJsonFile::create(Factory::getComposerFile());

        foreach($this->getModules() as $modulePackageName => $module) {
            $moduleRelativePath = GeneratorHelper::makeRelativePath($appBasePath, $module->getPath());
            if ($moduleRelativePath !== null) {
                $composerJsonFile->addModule($modulePackageName, $moduleRelativePath);
            }
        }

        $composerJsonFile->save();
        chdir($initialWorkingDir);

        $modulePackageNames = array_keys($this->getModules());
        if (! $modulePackageNames) {
            return;
        }

        $composerClass = Composer::class;
        $composer = $this->app[$composerClass];
        if (! ($composer instanceof Composer)) {
            throw ComposerException::make("$composerClass not registered in your app.");
        }
        if (! $composer->updatePackages($modulePackageNames, false, $output)) {
            throw ComposerException::make("Failed to update package with composer.");
        }
    }

    /**
     * @return array<string, array{
     *     path: string,
     *     packageName: string,
     *     name: string,
     *     namespace: string,
     *     providers: array<int, class-string>,
     *     aliases: array<string, class-string>
     * }>
     */
    public function toArray(): array
    {
        return array_map(static fn (Module $module) => $module->toArray(), $this->getModules());
    }

    /**
     * Write the given manifest array to disk.
     *
     * @throws Exception
     */
    protected function write(array $manifest, string $manifestPath): void
    {
        if (! is_writable($dirname = dirname($manifestPath))) {
            throw DirectoryMustBePresentAndWritable::make($dirname);
        }

        $this->filesystem->replace(
            $manifestPath,
            '<?php return '.var_export($manifest, true).';'
        );
    }

    /**
     * Normalize scan path
     */
    protected function normalizeScanPath(string $path): ?string
    {
        return Str::finish(GeneratorHelper::normalizePath($path, true), '/*');
    }

    /**
     * @param string $packageName
     * @param array{ path: string, name: string, namespace?: string, providers: class-string[], aliases: array<string, class-string> } $moduleData
     *
     * @return array{ path: string, name: string, namespace: string, providers: class-string[], aliases: array<string, class-string> }
     *
     * @throws ModuleHasNoNamespace
     */
    protected function validateModuleData(string $packageName, array $moduleData): array
    {
        if (empty(trim($moduleData['namespace'] ?? ""))) {
            throw ModuleHasNoNamespace::make($packageName);
        }

        return $moduleData;
    }

    /**
     * Make Module instances from manifest
     *
     * @param array<string, array{ path: string, name: string, namespace: string, providers: class-string[], aliases: array<string, class-string> }> $manifest
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
     * @param array{ path: string, name: string, namespace: string, providers: class-string[], aliases: array<string, class-string> } $moduleData
     * @return Module
     */
    protected function makeModuleFromManifestItem(string $packageName, array $moduleData): Module
    {
        return new Module(
            app: $this->app,
            modulesRepository: $this,
            packageName: $packageName,
            name: $moduleData['name'],
            path: $moduleData['path'],
            namespace: $moduleData['namespace'],
            providers: $moduleData['providers'],
            aliases: $moduleData['aliases'],
        );
    }
}
