<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Laraneat\Modules\Commands\BaseCommand;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Exceptions\NameIsReserved;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModulesRepository;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use LogicException;

abstract class BaseComponentGeneratorCommand extends BaseCommand
{
    /**
     * Reserved names that cannot be used for generation.
     *
     * @var string[]
     */
    protected array $reservedNames = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
    ];

    public function __construct(
        ModulesRepository $modulesRepository,
        protected Filesystem $filesystem
    ) {
        parent::__construct($modulesRepository);
    }

    /**
     * @param string $name
     * @return void
     *
     * @throws NameIsReserved
     */
    protected function ensureNameIsNotReserved(string $name): void
    {
        $classBaseName = class_basename($name);

        if ($this->isReservedName($classBaseName)) {
            throw NameIsReserved::make($classBaseName);
        }
    }

    /**
     * Generate component file.
     * @return int The function returns "0" on success and "1" on failure.
     */
    protected function generate(string $path, string $contents, bool $force = false): int
    {
        if ($force === false && $this->filesystem->exists($path)) {
            $this->components->error("File: `$path` already exists.");

            return self::FAILURE;
        }

        $path = str_replace('\\', '/', $path);
        $contents = $this->sortImports($contents);

        $this->filesystem->ensureDirectoryExists(dirname($path));

        if ($this->filesystem->put($path, $contents) !== false) {
            $this->components->info("Created: `$path`");

            return self::SUCCESS;
        }

        $this->components->error("Failed to create file: `$path`");

        return self::FAILURE;
    }

    /**
     * Get full class name from option or ask
     */
    public function getFullClassFromOptionOrAsk(
        string              $optionName,
        string              $question,
        ModuleComponentType $componentType,
        Module              $module
    ): string {
        return $this->getFullClass(
            $this->getOptionOrAsk(
                $optionName,
                $question,
            ),
            GeneratorHelper::component($componentType)->getFullNamespace($module)
        );
    }

    /**
     * Get full class name with namespace
     */
    public function getFullClass(string $class, string $defaultNamespace): string
    {
        if (Str::startsWith($class, '\\')) {
            return $class;
        }

        return trim($defaultNamespace, '\\') . '\\' . trim($class, '\\');
    }

    /**
     * Get the class "namespace" of the given class.
     */
    protected function getNamespaceOfClass(string $class): string
    {
        if (! Str::contains($class, '\\')) {
            return '';
        }

        return rtrim(Str::beforeLast($class, '\\'), '\\');
    }

    /**
     * Get component namespace, without the class name.
     */
    protected function getComponentNamespace(Module $module, string $name, ModuleComponentType $componentType): string
    {
        $name = str_replace('/', '\\', $name);
        $componentNamespace = GeneratorHelper::component($componentType)->getFullNamespace($module);
        $subNamespace = $this->getNamespaceOfClass($name);

        $namespace = $componentNamespace . '\\' . $subNamespace;

        return trim($namespace, '\\');
    }

    /**
     * Get component path
     */
    protected function getComponentPath(
        Module $module,
        string $name,
        ModuleComponentType $componentType,
        string $extension = '.php'
    ): string {
        $componentPath = GeneratorHelper::component($componentType)->getFullPath($module);
        $fileName = $this->convertNamespaceToPath($name);

        return $componentPath . '/' . $fileName . $extension;
    }

    protected function getUserModelClass(): string
    {
        $userModel = GeneratorHelper::getUserModelClass();

        if (! $userModel) {
            $userModel = $this->ask('Enter the class name of the "User model"');

            if (empty($userModel)) {
                throw new LogicException('The "User model" option is required');
            }
        }

        return $userModel;
    }

    protected function getCreatePermissionActionClass(): string
    {
        $createPermissionAction = GeneratorHelper::getCreatePermissionActionClass();

        if (! $createPermissionAction) {
            $createPermissionAction = $this->ask('Enter the class name of the "Create permission action"');

            if (empty($createPermissionAction)) {
                throw new LogicException('The "Create permission action" option is required');
            }
        }

        return $createPermissionAction;
    }

    protected function getCreatePermissionDTOClass(): string
    {
        $createPermissionAction = GeneratorHelper::getCreatePermissionDTOClass();

        if (! $createPermissionAction) {
            $createPermissionAction = $this->ask('Enter the class name of the "Create permission DTO"');

            if (empty($createPermissionAction)) {
                throw new LogicException('The "Create permission DTO" option is required');
            }
        }

        return $createPermissionAction;
    }

    /**
     * Checks whether the given name is reserved.
     */
    protected function isReservedName(string $name): bool
    {
        $name = strtolower($name);

        return in_array($name, $this->reservedNames, true);
    }

    /**
     * Alphabetically sorts the imports for the given stub.
     */
    protected function sortImports(string $stubContent): string
    {
        if (preg_match('/(?P<imports>(?:use [^;]+;$\n?)+)/m', $stubContent, $match)) {
            $imports = explode("\n", trim($match['imports']));

            sort($imports);
            $imports = array_unique($imports);

            return str_replace(trim($match['imports']), implode("\n", $imports), $stubContent);
        }

        return $stubContent;
    }

    /**
     * Get the first view directory path from the application configuration.
     */
    protected function viewPath(string $path = ''): string
    {
        $views = $this->laravel['config']['view.paths'][0] ?? resource_path('views');

        return $views.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Check if the package is installed
     */
    protected function packageIsInstalled(string $packageName): bool
    {
        $vendorPath = Env::get('COMPOSER_VENDOR_DIR') ?: $this->laravel->basePath('/vendor');
        $installedJsonPath = $vendorPath . '/composer/installed.json';

        if (! $this->filesystem->exists($installedJsonPath)) {
            return false;
        }

        /** @var array{packages?: array} $installed */
        $installed = json_decode($this->filesystem->get($installedJsonPath), true);

        foreach($installed['packages'] ?? [] as $package) {
            if ($package['name'] === $packageName) {
                return true;
            }
        }

        return false;
    }

    protected function ensurePackageIsInstalledOrWarn(string $packageName): void
    {
        if ($this->packageIsInstalled($packageName)) {
            $this->components->warn("Package '$packageName' is not installed!");
            $this->components->warn("Please install by entering `composer require $packageName` on the command line");
        }
    }
}
