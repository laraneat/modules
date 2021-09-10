<?php

namespace Laraneat\Modules\Generators;

use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command as Console;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\FileRepository;
use Laraneat\Modules\Support\Config\GenerateConfigReader;
use Laraneat\Modules\Support\Stub;

class ModuleGenerator extends Generator
{
    /**
     * The module name will created.
     *
     * @var string
     */
    protected string $name;

    /**
     * The repository instance.
     *
     * @var FileRepository|null
     */
    protected ?FileRepository $repository;

    /**
     * The laravel config instance.
     *
     * @var Config|null
     */
    protected ?Config $config;

    /**
     * The laravel filesystem instance.
     *
     * @var Filesystem|null
     */
    protected ?Filesystem $filesystem;

    /**
     * The laravel console instance.
     *
     * @var Console|null
     */
    protected ?Console $console;

    /**
     * The activator instance
     *
     * @var ActivatorInterface|null
     */
    protected ?ActivatorInterface $activator;

    /**
     * Force status.
     *
     * @var bool
     */
    protected bool $force = false;

    /**
     * set default module type.
     *
     * @var string
     */
    protected string $type = 'web';

    /**
     * Enables the module.
     *
     * @var bool
     */
    protected bool $isActive = false;

    /**
     * The constructor.
     * @param $name
     * @param FileRepository|null $repository
     * @param Config|null $config
     * @param Filesystem|null $filesystem
     * @param Console|null $console
     * @param ActivatorInterface|null $activator
     */
    public function __construct(
        $name,
        ?FileRepository $repository = null,
        ?Config $config = null,
        ?Filesystem $filesystem = null,
        ?Console $console = null,
        ?ActivatorInterface $activator = null
    ) {
        $this->name = $name;
        $this->repository = $repository;
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->console = $console;
        $this->activator = $activator;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set active flag.
     *
     * @param bool $active
     *
     * @return $this
     */
    public function setActive(bool $active)
    {
        $this->isActive = $active;

        return $this;
    }

    /**
     * Get the name of module will created. By default in studly case.
     *
     * @return string
     */
    public function getName(): string
    {
        return Str::studly($this->name);
    }

    /**
     * Get the laravel config instance.
     *
     * @return Config|null
     */
    public function getConfig(): ?Config
    {
        return $this->config;
    }

    /**
     * Set the laravel config instance.
     *
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Set the modules activator
     *
     * @param ActivatorInterface $activator
     *
     * @return $this
     */
    public function setActivator(ActivatorInterface $activator)
    {
        $this->activator = $activator;

        return $this;
    }

    /**
     * Get the laravel filesystem instance.
     *
     * @return Filesystem|null
     */
    public function getFilesystem(): ?Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Set the laravel filesystem instance.
     *
     * @param Filesystem $filesystem
     *
     * @return $this
     */
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Get the laravel console instance.
     *
     * @return Console|null
     */
    public function getConsole(): ?Console
    {
        return $this->console;
    }

    /**
     * Set the laravel console instance.
     *
     * @param Console $console
     *
     * @return $this
     */
    public function setConsole(Console $console)
    {
        $this->console = $console;

        return $this;
    }

    /**
     * Get the repository instance.
     *
     * @return FileRepository|null
     */
    public function getRepository(): ?FileRepository
    {
        return $this->repository;
    }

    /**
     * Set the repository instance.
     *
     * @param FileRepository $repository
     *
     * @return $this
     */
    public function setRepository(FileRepository $repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Get the list of folders will created.
     *
     * @return array
     */
    public function getFolders()
    {
        return $this->repository->config('paths.generator');
    }

    /**
     * Get the list of files will created.
     *
     * @return array
     */
    public function getFiles(): array
    {
        return $this->repository->config('stubs.files');
    }

    /**
     * Set force status.
     *
     * @param bool|int $force
     *
     * @return $this
     */
    public function setForce($force)
    {
        $this->force = $force;

        return $this;
    }

    /**
     * Generate the module.
     * @throws ModuleNotFoundException
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

        $this->generateModuleJsonFile();

        if ($this->type !== 'plain') {
            $this->generateFiles();
            $this->generateResources();
        }

        if ($this->type === 'plain') {
            $this->cleanModuleJsonFile();
        }

        $this->activator->setActiveByName($name, $this->isActive);

        $this->console->info("Module [{$name}] created successfully.");

        return 0;
    }

    /**
     * Generate the folders.
     */
    public function generateFolders(): void
    {
        foreach ($this->getFolders() as $key => $folder) {
            $folder = GenerateConfigReader::read($key);

            if ($folder->generate() === false) {
                continue;
            }

            $path = $this->repository->getModulePath($this->getName()) . '/' . $folder->getPath();

            $this->filesystem->makeDirectory($path, 0755, true);
            if (config('modules.stubs.gitkeep')) {
                $this->generateGitKeep($path);
            }
        }
    }

    /**
     * Generate git keep to the specified path.
     *
     * @param string $path
     */
    public function generateGitKeep(string $path): void
    {
        $this->filesystem->put($path . '/.gitkeep', '');
    }

    /**
     * Generate the files.
     */
    public function generateFiles(): void
    {
        foreach ($this->getFiles() as $stub => $file) {
            $path = $this->repository->getModulePath($this->getName()) . $file;

            if (!$this->filesystem->isDirectory($dir = dirname($path))) {
                $this->filesystem->makeDirectory($dir, 0775, true);
            }

            $this->filesystem->put($path, $this->getStubContents($stub));

            $this->console->info("Created : {$path}");
        }
    }

    /**
     * Generate some resources.
     */
    public function generateResources(): void
    {
        if (GenerateConfigReader::read('seeder')->generate() === true) {
            $this->console->call('module:make-seed', [
                'name' => $this->getName(),
                'module' => $this->getName(),
                '--master' => true,
            ]);
        }

        if (GenerateConfigReader::read('provider')->generate() === true) {
            $this->console->call('module:make-provider', [
                'name' => $this->getName() . 'ServiceProvider',
                'module' => $this->getName(),
                '--master' => true,
            ]);
            $this->console->call('module:route-provider', [
                'module' => $this->getName(),
            ]);
        }

        if (GenerateConfigReader::read('controller')->generate() === true) {
            $options = $this->type === 'api' ? ['--api'=>true] : [];
            $this->console->call('module:make-controller', [
                'controller' => $this->getName() . 'Controller',
                'module' => $this->getName(),
            ]+$options);
        }
    }

    /**
     * Get the contents of the specified stub file by given stub name.
     *
     * @param string $stub
     *
     * @return string
     */
    protected function getStubContents(string $stub): string
    {
        return (new Stub(
            '/' . $stub . '.stub',
            $this->getReplacement($stub)
        )
        )->render();
    }

    /**
     * get the list for the replacements.
     */
    public function getReplacements()
    {
        return $this->repository->config('stubs.replacements');
    }

    /**
     * Get array replacement for the specified stub.
     *
     * @param string $stub
     *
     * @return array
     */
    protected function getReplacement(string $stub): array
    {
        $replacements = $this->repository->config('stubs.replacements');

        if (!isset($replacements[$stub])) {
            return [];
        }

        $keys = $replacements[$stub];

        $replaces = [];

        if ($stub === 'json' || $stub === 'composer') {
            if (in_array('PROVIDER_NAMESPACE', $keys, true) === false) {
                $keys[] = 'PROVIDER_NAMESPACE';
            }
        }
        foreach ($keys as $key) {
            if (method_exists($this, $method = 'get' . ucfirst(Str::studly(strtolower($key))) . 'Replacement')) {
                $replaces[$key] = $this->$method();
            } else {
                $replaces[$key] = null;
            }
        }

        return $replaces;
    }

    /**
     * Generate the module.json file
     */
    private function generateModuleJsonFile(): void
    {
        $path = $this->repository->getModulePath($this->getName()) . 'module.json';

        if (!$this->filesystem->isDirectory($dir = dirname($path))) {
            $this->filesystem->makeDirectory($dir, 0775, true);
        }

        $this->filesystem->put($path, $this->getStubContents('json'));

        $this->console->info("Created : {$path}");
    }

    /**
     * Remove the default service provider that was added in the module.json file
     * This is needed when a --plain module was created
     */
    private function cleanModuleJsonFile(): void
    {
        $path = $this->repository->getModulePath($this->getName()) . 'module.json';

        $content = $this->filesystem->get($path);
        $namespace = $this->getModuleNamespaceReplacement();
        $studlyName = $this->getStudlyNameReplacement();

        $provider = '"' . $namespace . '\\\\' . $studlyName . '\\\\Providers\\\\' . $studlyName . 'ServiceProvider"';

        $content = str_replace($provider, '', $content);

        $this->filesystem->put($path, $content);
    }

    /**
     * Get the module name in lower case.
     *
     * @return string
     */
    protected function getLowerNameReplacement(): string
    {
        return strtolower($this->getName());
    }

    /**
     * Get the module name in studly case.
     *
     * @return string
     */
    protected function getStudlyNameReplacement(): string
    {
        return $this->getName();
    }

    /**
     * Get replacement for $VENDOR$.
     *
     * @return string
     */
    protected function getVendorReplacement(): string
    {
        return $this->repository->config('composer.vendor', '');
    }

    /**
     * Get replacement for $MODULE_NAMESPACE$.
     *
     * @return string
     */
    protected function getModuleNamespaceReplacement(): string
    {
        return str_replace('\\', '\\\\', $this->repository->config('namespace', ''));
    }

    /**
     * Get replacement for $AUTHOR_NAME$.
     *
     * @return string
     */
    protected function getAuthorNameReplacement(): string
    {
        return $this->repository->config('composer.author.name', '');
    }

    /**
     * Get replacement for $AUTHOR_EMAIL$.
     *
     * @return string
     */
    protected function getAuthorEmailReplacement(): string
    {
        return $this->repository->config('composer.author.email', '');
    }

    /**
     * Get replacement for $PROVIDER_NAMESPACE$.
     *
     * @return string
     */
    protected function getProviderNamespaceReplacement(): string
    {
        return str_replace('\\', '\\\\', GenerateConfigReader::read('provider')->getNamespace());
    }
}
