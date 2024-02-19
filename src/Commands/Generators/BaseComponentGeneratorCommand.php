<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laraneat\Modules\Commands\BaseCommand;
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
    )
    {
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

    protected function generate(string $path, string $contents, bool $force = false): int
    {
        if ($force === false && $this->filesystem->exists($path)) {
            $this->components->error("File: `$path` already exists.");

            return self::FAILURE;
        }

        $path = str_replace('\\', '/', $path);
        $contents = $this->sortImports($contents);

        $this->filesystem->ensureDirectoryExists(dirname($path));
        $this->filesystem->put($path, $contents);
        $this->components->info("Created: `$path`");

        return self::SUCCESS;
    }

    /**
     * Get full class name from option or ask
     */
    public function getFullClassFromOptionOrAsk(
        string $optionName,
        string $question,
        string $componentType,
        Module $module
    ): string
    {
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
        if (class_exists($class) || Str::contains($class, '\\')) {
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
    protected function getComponentNamespace(Module $module, string $name, string $componentType): string
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
        string $componentType,
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
     * Convert namespace to path
     */
    protected function convertNamespaceToPath(string $namespace): string
    {
        return trim(str_replace('\\', '/', $namespace), '/');
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
}
