<?php

namespace Laraneat\Modules\Generators;

use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command as Console;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\FileRepository;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Laraneat\Modules\Support\Stub;

class ModuleGenerator extends Generator
{
    /**
     * The name of the module to be created.
     */
    protected string $name;

    /**
     * Entity name.
     */
    protected string $entityName;

    /**
     * The repository instance.
     */
    protected ?FileRepository $repository;

    /**
     * The laravel config instance.
     */
    protected ?Config $config;

    /**
     * The laravel filesystem instance.
     */
    protected ?Filesystem $filesystem;

    /**
     * The laravel console instance.
     */
    protected ?Console $console;

    /**
     * The activator instance
     */
    protected ?ActivatorInterface $activator;

    /**
     * Force status.
     */
    protected bool $force = false;

    /**
     * The module type.
     */
    protected string $type = 'api';

    /**
     * Enables the module.
     */
    protected bool $isActive = false;

    public function __construct(
        string $name,
        ?string $entityName = null,
        ?FileRepository $repository = null,
        ?Config $config = null,
        ?Filesystem $filesystem = null,
        ?Console $console = null,
        ?ActivatorInterface $activator = null
    ) {
        $this->name = $name;
        $this->setEntityName($entityName ?: $name);
        $this->repository = $repository;
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->console = $console;
        $this->activator = $activator;
    }

    /**
     * Set entity name.
     */
    public function setEntityName(string $entityName): static
    {
        $this->entityName = Str::studly($entityName);

        return $this;
    }

    /**
     * Set type.
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set active flag.
     */
    public function setActive(bool $active): static
    {
        $this->isActive = $active;

        return $this;
    }

    /**
     * Get the name of the module to create. By default, in studly case.
     */
    public function getName(): string
    {
        return Str::studly($this->name);
    }

    /**
     * Get the entity name.
     */
    public function getEntityName(): string
    {
        return $this->entityName ?: $this->getName();
    }

    /**
     * Get the laravel config instance.
     */
    public function getConfig(): ?Config
    {
        return $this->config;
    }

    /**
     * Set the laravel config instance.
     */
    public function setConfig(Config $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Set the modules activator
     */
    public function setActivator(ActivatorInterface $activator): static
    {
        $this->activator = $activator;

        return $this;
    }

    /**
     * Get the laravel filesystem instance.
     */
    public function getFilesystem(): ?Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Set the laravel filesystem instance.
     */
    public function setFilesystem(Filesystem $filesystem): static
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Get the laravel console instance.
     */
    public function getConsole(): ?Console
    {
        return $this->console;
    }

    /**
     * Set the laravel console instance.
     */
    public function setConsole(Console $console): static
    {
        $this->console = $console;

        return $this;
    }

    /**
     * Get the repository instance.
     */
    public function getRepository(): ?FileRepository
    {
        return $this->repository;
    }

    /**
     * Set the repository instance.
     */
    public function setRepository(FileRepository $repository): static
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Get the list of folders to be created.
     */
    public function getFolders(): array
    {
        return $this->repository->config('generator.components');
    }

    /**
     * Set force status.
     */
    public function setForce($force): static
    {
        $this->force = $force;

        return $this;
    }

    /**
     * Generate the module.
     */
    public function generate(): int
    {
        $name = $this->getName();

        if ($this->repository->has($name)) {
            if ($this->force) {
                $this->repository->delete($name);
            } else {
                $this->console->error("Module [{$name}] already exist!");

                return E_ERROR;
            }
        }

        $this->generateFolders();
        $this->generateScaffoldFiles();

        $this->activator->setActiveByName($name, $this->isActive);
        $this->repository->flushCache();

        $this->generateProviders();

        if ($this->type && $this->type !== 'plain') {
            $module = $this->repository->findOrFail($name);
            $code = (new ModuleComponentsGenerator($module))
                ->setEntityName($this->entityName)
                ->setConsole($this->console)
                ->generate();

            if ($code === $this->console::FAILURE) {
                return $this->console::FAILURE;
            }
        }

        $this->console->info("Module [{$name}] created successfully.");

        return $this->console::SUCCESS;
    }

    /**
     * Generate the folders.
     */
    public function generateFolders(): void
    {
        foreach ($this->getFolders() as $key => $folder) {
            $folder = GeneratorHelper::component($key);

            if ($folder->generate() === false) {
                continue;
            }

            $path = GeneratorHelper::modulePath($this->getName(), $folder->getPath());

            if (!$this->filesystem->isDirectory($path)) {
                $this->filesystem->makeDirectory($path, 0755, true);
                if ($folder->withGitKeep()) {
                    $this->generateGitKeep($path);
                }
            }
        }
    }

    public function generateScaffoldFiles(): void
    {
        $this->generateComposerJsonFile();
        $this->generateModuleJsonFile();
        $this->generateConfig();
    }

    /**
     * Generate module providers.
     */
    public function generateProviders(): void
    {
        if (! GeneratorHelper::component('provider')->generate()) {
            return;
        }

        $moduleName = $this->getName();

        $this->console->call('module:make:provider', [
            'name' => "{$moduleName}ServiceProvider",
            'module' => $moduleName,
            '--stub' => 'module'
        ]);

        $this->console->call('module:make:provider', [
            'name' => "RouteServiceProvider",
            'module' => $moduleName,
            '--stub' => 'route'
        ]);
    }

    /**
     * Generate git keep to the specified path.
     */
    protected function generateGitKeep(string $path): void
    {
        $this->filesystem->put($path . '/.gitkeep', '');
    }

    /**
     * Generate config
     */
    protected function generateConfig(): void
    {
        if (! GeneratorHelper::component('config')->generate()) {
            return;
        }
        $configFileName = Str::snake($this->getName(), '-') . '-module';
        $subPath = GeneratorHelper::component('config')->getPath() . '/' . $configFileName . '.php';
        $path = GeneratorHelper::modulePath($this->getName(), $subPath);

        $stubContent = (new Stub('/config.stub', $this->getAllReplacements()))->render();

        $this->createFile($path, $stubContent);
    }

    /**
     * Generate config
     */
    protected function generateComposerJsonFile(): void
    {
        $path = GeneratorHelper::modulePath($this->getName(), 'composer.json');

        $stubContent = (new Stub('/composer.json.stub', $this->getAllReplacements()))->render();

        $this->createFile($path, $stubContent);
    }

    /**
     * Generate the module.json file
     */
    protected function generateModuleJsonFile(): void
    {
        $path = GeneratorHelper::modulePath($this->getName(), 'module.json');

        $stubContent = (new Stub('/module.json.stub', $this->getAllReplacements()))->render();

        $this->createFile($path, $stubContent);
    }

    /**
     * Create a file at the given path, after creating folders if necessary
     */
    protected function createFile(string $path, string $content): void
    {
        $this->filesystem->ensureDirectoryExists(dirname($path));
        $this->filesystem->put($path, $content);

        $this->console->info("Created: `$path`");
    }

    /**
     * Get replacements
     */
    protected function getAllReplacements(): array
    {
        return [
            'authorEmail' => $this->getAuthorEmailReplacement(),
            'authorName' => $this->getAuthorNameReplacement(),
            'moduleKey' => $this->getModuleKeyReplacement(),
            'moduleName' => $this->getModuleNameReplacement(),
            'moduleNamespace' => $this->getModuleNamespaceReplacement(),
            'vendor' => $this->getVendorReplacement()
        ];
    }

    /**
     * Get replacement for {{ moduleKey }}.
     */
    protected function getModuleKeyReplacement(): string
    {
        return Str::snake($this->getName(), '-');
    }

    /**
     * Get the module name in studly case (replacement for {{ moduleName }}).
     */
    protected function getModuleNameReplacement(): string
    {
        return $this->getName();
    }

    /**
     * Get replacement for {{ vendor }}.
     */
    protected function getVendorReplacement(): string
    {
        return $this->repository->config('composer.vendor', '');
    }

    /**
     * Get replacement for {{ authorName }}.
     */
    protected function getAuthorNameReplacement(): string
    {
        return $this->repository->config('composer.author.name', '');
    }

    /**
     * Get replacement for {{ authorEmail }}.
     */
    protected function getAuthorEmailReplacement(): string
    {
        return $this->repository->config('composer.author.email', '');
    }

    /**
     * Get replacement for {{ moduleNamespace }}.
     */
    protected function getModuleNamespaceReplacement(): string
    {
        return str_replace('\\', '\\\\', GeneratorHelper::moduleNamespace($this->getName()));
    }
}
