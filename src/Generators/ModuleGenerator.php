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
     * Get the list of folders to be created.
     *
     * @return array
     */
    public function getFolders(): array
    {
        return $this->repository->config('generator.components');
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
     *
     * @return int
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

        if ($this->type === 'plain') {
            $this->cleanModuleJsonFile();
        }

        $this->activator->setActiveByName($name, $this->isActive);
        $this->repository->flushCache();

        if ($this->type !== 'plain') {
            $this->generateComponents();
        }

        $this->console->info("Module [{$name}] created successfully.");

        return 0;
    }

    /**
     * Generate the folders.
     *
     * @return void
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
     * Generate module components.
     *
     * @return void
     */
    public function generateComponents(): void
    {
        $actionVerbs = [
            'api' => ['create', 'update', 'delete', 'list', 'view'],
            'web' => ['create', 'update', 'delete'],
        ];
        $studlyName = $this->getName();
        $studlyPluralName = Str::plural($studlyName);
        $camelName = Str::camel($studlyName);
        $snakeName = Str::snake($studlyName);
        $snakePluralName = Str::plural($snakeName);
        $dashedPluralName = Str::snake($snakePluralName, '-');
        $underlinedPluralName = Str::snake($snakePluralName, '_');

        if (GeneratorHelper::component('action')->generate() === true) {
            foreach ($actionVerbs['api'] as $actionVerb) {
                $studlyActionVerb = Str::studly($actionVerb);
                $actionClass = "{$studlyActionVerb}{$studlyName}Action";
                $requestClass = "{$studlyActionVerb}{$studlyName}Request";
                $wizardClass = "{$studlyName}QueryWizard";

                if ($actionVerb === "list") {
                    $actionClass = "{$studlyActionVerb}{$studlyPluralName}Action";
                    $requestClass = "{$studlyActionVerb}{$studlyPluralName}Request";
                    $wizardClass = "{$studlyPluralName}QueryWizard";
                }

                $this->console->call('module:make:action', [
                    'name' => $actionClass,
                    'module' => $studlyName,
                    '--stub' => $actionVerb,
                    '--model' => $studlyName,
                    '--request' => $requestClass,
                    '--resource' => "{$studlyName}Resource",
                    '--wizard' => $wizardClass
                ]);
            }
        }

        if (GeneratorHelper::component('factory')->generate() === true) {
            $this->console->call('module:make:factory', [
                'name' => "{$studlyName}Factory",
                'module' => $studlyName,
                '--model' => $studlyName
            ]);
        }

        if (GeneratorHelper::component('migration')->generate() === true) {
            $this->console->call('module:make:migration', [
                'name' => "create_{$snakePluralName}_table",
                'module' => $studlyName,
                '--stub' => 'create'
            ]);
        }

        if (GeneratorHelper::component('seeder')->generate() === true) {
            $this->console->call('module:make:seeder', [
                'name' => "{$studlyName}PermissionsSeeder_1",
                'module' => $studlyName,
                '--stub' => 'permissions',
                '--model' => $studlyName
            ]);
        }

        if (GeneratorHelper::component('model')->generate() === true) {
            $this->console->call('module:make:model', [
                'name' => $studlyName,
                'module' => $studlyName,
                '--stub' => 'full',
                '--factory' => "{$studlyName}Factory"
            ]);
        }

        if (GeneratorHelper::component('policy')->generate() === true) {
            $this->console->call('module:make:policy', [
                'name' => "{$studlyName}Policy",
                'module' => $studlyName,
                '--stub' => 'full',
                '--model' => $studlyName
            ]);
        }

        if (GeneratorHelper::component('provider')->generate() === true) {
            $this->console->call('module:make:provider', [
                'name' => "{$studlyName}ServiceProvider",
                'module' => $studlyName,
                '--stub' => 'module'
            ]);
            $this->console->call('module:make:provider', [
                'name' => "RouteServiceProvider",
                'module' => $studlyName,
                '--stub' => 'route'
            ]);
        }

        if (GeneratorHelper::component('api-query-wizard')->generate() === true) {
            $this->console->call('module:make:wizard', [
                'name' => "{$studlyPluralName}QueryWizard",
                'module' => $studlyName,
                '--stub' => 'eloquent',
            ]);
            $this->console->call('module:make:wizard', [
                'name' => "{$studlyName}QueryWizard",
                'module' => $studlyName,
                '--stub' => 'model',
            ]);
        }

        if (GeneratorHelper::component('api-resource')->generate() === true) {
            $this->console->call('module:make:resource', [
                'name' => "{$studlyName}Resource",
                'module' => $studlyName,
                '--stub' => 'single'
            ]);
        }

        foreach ($actionVerbs as $ui => $uiActionVerbs) {
            if (GeneratorHelper::component("{$ui}-controller")->generate() === true) {
                $this->console->call('module:make:controller', [
                    'name' => 'Controller',
                    'module' => $studlyName,
                    '--ui' => $ui
                ]);
            }

            if (GeneratorHelper::component("{$ui}-request")->generate() === true) {
                foreach ($uiActionVerbs as $actionVerb) {
                    $studlyActionName = Str::studly($actionVerb);
                    $requestClass = $actionVerb === 'list'
                        ? "{$studlyActionName}{$studlyPluralName}Request"
                        : "{$studlyActionName}{$studlyName}Request";
                    $this->console->call('module:make:request', [
                        'name' => $requestClass,
                        'module' => $studlyName,
                        '--ui' => $ui,
                        '--stub' => $actionVerb,
                        '--model' => $studlyName,
                    ]);
                }
            }

            if (GeneratorHelper::component("{$ui}-route")->generate() === true) {
                $actionMethodsMap = [
                    'create' => 'post',
                    'update' => 'patch',
                    'delete' => 'delete',
                    'list' => 'get',
                    'view' => 'get'
                ];
                foreach ($uiActionVerbs as $actionVerb) {
                    $studlyActionName = Str::studly($actionVerb);
                    $actionClass = $actionVerb === 'list'
                        ? "{$studlyActionName}{$studlyPluralName}Action"
                        : "{$studlyActionName}{$studlyName}Action";

                    $url = $dashedPluralName;
                    if (in_array($actionVerb, ['update', 'delete', 'view'])) {
                        $url .= '/{' . $camelName . '}';
                    }

                    $filePath = Str::snake(str_replace('Action', '', $actionClass), '_');
                    if ($ui === 'api') {
                        $filePath = 'v1/' . $filePath;
                    }

                    $this->console->call('module:make:route', [
                        'name' => $filePath,
                        'module' => $studlyName,
                        '--ui' => $ui,
                        '--action' => $actionClass,
                        '--method' => $actionMethodsMap[$actionVerb],
                        '--url' => $url,
                        '--name' => $ui . '.' . $underlinedPluralName . '.' . $actionVerb
                    ]);
                }
            }

            if (GeneratorHelper::component("{$ui}-test")->generate() === true) {
                foreach ($uiActionVerbs as $actionVerb) {
                    $studlyActionName = Str::studly($actionVerb);
                    $testClass = $actionVerb === 'list'
                        ? "{$studlyActionName}{$studlyPluralName}Test"
                        : "{$studlyActionName}{$studlyName}Test";

                    $url = "/api/v1/{$dashedPluralName}";
                    if (in_array($actionVerb, ['update', 'delete', 'view'])) {
                        $url .= '/{' . $camelName . '}';
                    }

                    $this->console->call('module:make:test', [
                        'name' => $testClass,
                        'module' => $studlyName,
                        '--type' => $ui,
                        '--stub' => $actionVerb,
                        '--model' => $studlyName,
                        '--url' => $url
                    ]);
                }
            }
        }
    }

    /**
     * Generate git keep to the specified path.
     *
     * @param string $path
     */
    protected function generateGitKeep(string $path): void
    {
        $this->filesystem->put($path . '/.gitkeep', '');
    }

    /**
     * Generate config
     *
     * @return void
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
     *
     * @return void
     */
    protected function generateComposerJsonFile(): void
    {
        $path = GeneratorHelper::modulePath($this->getName(), 'composer.json');

        $stubContent = (new Stub('/composer.json.stub', $this->getAllReplacements()))->render();

        $this->createFile($path, $stubContent);
    }

    /**
     * Generate the module.json file
     *
     * @return void
     */
    protected function generateModuleJsonFile(): void
    {
        $path = GeneratorHelper::modulePath($this->getName(), 'module.json');

        $stubContent = (new Stub('/module.json.stub', $this->getAllReplacements()))->render();

        $this->createFile($path, $stubContent);
    }

    /**
     * Remove the default service provider that was added in the module.json file
     * This is needed when a --plain module was created
     *
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function cleanModuleJsonFile(): void
    {
        $path = GeneratorHelper::modulePath($this->getName(), 'module.json');

        $content = $this->filesystem->get($path);
        $providerNamespace = $this->getProviderNamespaceReplacement();
        $studlyName = $this->getStudlyNameReplacement();

        $provider = '"' . $providerNamespace . '\\\\' . $studlyName . 'ServiceProvider"';

        $content = str_replace($provider, '', $content);

        $this->filesystem->put($path, $content);
    }

    /**
     * Create a file at the given path, after creating folders if necessary
     *
     * @param string $path
     * @param string $content
     *
     * @return void
     */
    protected function createFile(string $path, string $content): void
    {
        $this->filesystem->ensureDirectoryExists(dirname($path));
        $this->filesystem->put($path, $content);

        $this->console->info("Created: `$path`");
    }

    /**
     * Get replacements
     *
     * @return array
     */
    protected function getAllReplacements(): array
    {
        return [
            'authorEmail' => $this->getAuthorEmailReplacement(),
            'authorName' => $this->getAuthorNameReplacement(),
            'lowerName' => $this->getLowerNameReplacement(),
            'studlyName' => $this->getStudlyNameReplacement(),
            'moduleNamespace' => $this->getModuleNamespaceReplacement(),
            'providerNamespace' => $this->getProviderNamespaceReplacement(),
            'vendor' => $this->getVendorReplacement()
        ];
    }

    /**
     * Get the module name in lower case (replacement for {{ lowerName }}).
     *
     * @return string
     */
    protected function getLowerNameReplacement(): string
    {
        return strtolower($this->getName());
    }

    /**
     * Get the module name in studly case (replacement for {{ studlyName }}).
     *
     * @return string
     */
    protected function getStudlyNameReplacement(): string
    {
        return $this->getName();
    }

    /**
     * Get replacement for {{ vendor }}.
     *
     * @return string
     */
    protected function getVendorReplacement(): string
    {
        return $this->repository->config('composer.vendor', '');
    }

    /**
     * Get replacement for {{ authorName }}.
     *
     * @return string
     */
    protected function getAuthorNameReplacement(): string
    {
        return $this->repository->config('composer.author.name', '');
    }

    /**
     * Get replacement for {{ authorEmail }}.
     *
     * @return string
     */
    protected function getAuthorEmailReplacement(): string
    {
        return $this->repository->config('composer.author.email', '');
    }

    /**
     * Get replacement for {{ moduleNamespace }}.
     *
     * @return string
     */
    protected function getModuleNamespaceReplacement(): string
    {
        return str_replace('\\', '\\\\', GeneratorHelper::moduleNamespace($this->getName()));
    }

    /**
     * Get replacement for {{ providerNamespace }}.
     *
     * @return string
     */
    protected function getProviderNamespaceReplacement(): string
    {
        $providerNamespace = GeneratorHelper::component('provider')->getFullNamespace($this->getName());
        return str_replace('\\', '\\\\', $providerNamespace);
    }
}
