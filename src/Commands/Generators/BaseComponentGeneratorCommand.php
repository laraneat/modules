<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Laraneat\Modules\Commands\BaseCommand;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Exceptions\InvalidClassName;
use Laraneat\Modules\Exceptions\InvalidTableName;
use Laraneat\Modules\Exceptions\ModuleHasNoNamespace;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Exceptions\NameIsReserved;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModulesRepository;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use LogicException;

abstract class BaseComponentGeneratorCommand extends BaseCommand
{
    /**
     * The module instance.
     */
    protected Module $module;

    /**
     * The 'name' argument.
     */
    protected string $nameArgument;

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType;
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
     * Execute the console command (Template Method).
     *
     * Subclasses can override:
     * - beforeGenerate(): void - hook before generation starts
     * - getContents(): string - must be implemented to return file contents
     * - getGeneratedFilePath(): string - override to customize file path
     * - afterGenerate(): void - hook after successful generation
     */
    public function handle(): int
    {
        $this->beforeGenerate();

        try {
            $this->nameArgument = $this->argument('name');
            $this->ensureNameIsNotReserved($this->nameArgument);
            $this->ensureNameIsValidClassName($this->nameArgument);
            $this->module = $this->getModuleArgumentOrFail();
        } catch (NameIsReserved|InvalidClassName|ModuleNotFound|ModuleHasNonUniquePackageName|ModuleHasNoNamespace $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        try {
            $result = $this->generate(
                $this->getGeneratedFilePath(),
                $this->getContents(),
                $this->option('force')
            );
        } catch (InvalidTableName $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result !== self::SUCCESS) {
            return $result;
        }

        $this->afterGenerate();

        return self::SUCCESS;
    }

    /**
     * Hook called before generation starts.
     * Override in subclasses to add custom logic (e.g., package checks).
     */
    protected function beforeGenerate(): void
    {
        // Default: do nothing
    }

    /**
     * Get the contents for the generated file.
     * Must be implemented by subclasses.
     */
    abstract protected function getContents(): string;

    /**
     * Get the path for the generated file.
     * Override in subclasses to customize (e.g., MigrationMakeCommand).
     */
    protected function getGeneratedFilePath(): string
    {
        return $this->getComponentPath($this->module, $this->nameArgument, $this->componentType);
    }

    /**
     * Hook called after successful generation.
     * Override in subclasses to add custom logic (e.g., ProviderMakeCommand).
     */
    protected function afterGenerate(): void
    {
        // Default: do nothing
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
     * Validate that the name is a valid PHP class name.
     *
     * @throws InvalidClassName
     */
    protected function ensureNameIsValidClassName(string $name): void
    {
        $classBaseName = class_basename($name);

        if (!$this->isValidClassName($classBaseName)) {
            throw InvalidClassName::make($classBaseName);
        }
    }

    /**
     * Check if the name is a valid PHP class name.
     */
    protected function isValidClassName(string $name): bool
    {
        // PHP class names must start with a letter or underscore,
        // followed by any number of letters, numbers, or underscores.
        // Also supports multibyte characters as per PHP specification.
        return (bool) preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name);
    }

    /**
     * Check if the name is a valid database table name.
     */
    protected function isValidTableName(string $name): bool
    {
        // Table names should contain only letters, numbers, and underscores,
        // and start with a letter or underscore.
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name);
    }

    /**
     * Generate component file.
     * @return int The function returns "0" on success and "1" on failure.
     */
    protected function generate(string $path, string $contents, bool $force = false): int
    {
        if ($force === false && $this->filesystem->exists($path)) {
            $this->components->error("File already exists: `$path`. Use --force to overwrite.");

            return self::FAILURE;
        }

        $path = str_replace('\\', '/', $path);
        $contents = $this->sortImports($contents);

        $directory = dirname($path);

        try {
            $this->filesystem->ensureDirectoryExists($directory);
        } catch (\Exception $e) {
            $this->components->error("Failed to create directory: `$directory`. " . $e->getMessage());

            return self::FAILURE;
        }

        if (!is_writable($directory)) {
            $this->components->error("Directory is not writable: `$directory`. Check file permissions.");

            return self::FAILURE;
        }

        if ($this->filesystem->put($path, $contents) !== false) {
            $this->components->info("Created: `$path`");

            return self::SUCCESS;
        }

        $this->components->error("Failed to write file: `$path`. Check disk space and file permissions.");

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
        $fileName = GeneratorHelper::normalizePath($name);

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
