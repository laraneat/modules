<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laraneat\Modules\Commands\BaseCommand;
use Laraneat\Modules\Exceptions\FileAlreadyExistException;
use Laraneat\Modules\Generators\FileGenerator;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use LogicException;

abstract class ComponentGeneratorCommand extends BaseCommand
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

    /**
     * Get template contents.
     *
     * @return string
     */
    abstract protected function getTemplateContents(): string;

    /**
     * Get the destination file path.
     *
     * @return string
     */
    abstract protected function getDestinationFilePath(): string;

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the component.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Dummy 'prepare' method
     *
     * @return mixed
     */
    protected function prepare()
    {
        //
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->prepare();

        // First we need to ensure that the given name is not a reserved word within the PHP
        // language and that the class name will actually be valid. If it is not valid we
        // can error now and prevent from polluting the filesystem using invalid files.
        if ($this->isReservedName($this->getClass($this->getTrimmedArgument('name')))) {
            $this->error('The name "'.$this->getClass($this->getTrimmedArgument('name')).'" is reserved by PHP.');

            return self::FAILURE;
        }

        $path = str_replace('\\', '/', $this->getDestinationFilePath());
        $contents = $this->sortImports($this->getTemplateContents());

        try {
            $overwriteFile = $this->hasOption('force') && $this->option('force');

            File::ensureDirectoryExists(dirname($path));
            (new FileGenerator($path, $contents))->withFileOverwrite($overwriteFile)->generate();

            $this->components->info("Created: `$path`");
        } catch (FileAlreadyExistException $e) {
            $this->error("File: `$path` already exists.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Get the class "basename" of the given class.
     *
     * @param string $class
     *
     * @return string
     */
    protected function getClass(string $class): string
    {
        return class_basename($class);
    }

    /**
     * Get the class "namespace" of the given class.
     *
     * @param string $class
     *
     * @return string
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
     *
     * @param Module $module
     * @param string $name
     * @param string $componentType
     *
     * @return string
     */
    protected function getComponentNamespace(Module $module, string $name, string $componentType): string
    {
        $name = str_replace('/', '\\', $name);
        $componentNamespace = GeneratorHelper::component($componentType)->getFullNamespace($module);
        $extraNamespace = $this->getNamespaceOfClass($name);

        $namespace = $componentNamespace . '\\' . $extraNamespace;

        return trim($namespace, '\\');
    }

    /**
     * Get component path, without the extension.
     *
     * @param Module $module
     * @param string $name
     * @param string $componentType
     * @param string $extension
     * @return string
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

    /**
     * @return string
     */
    protected function getUserModelClass(): string
    {
        $userModel = GeneratorHelper::userModel();

        if (! $userModel) {
            $userModel = $this->ask('Enter the class name of the "User model"');

            if (empty($userModel)) {
                throw new LogicException('The "User model" option is required');
            }
        }

        return $userModel;
    }

    /**
     * @return string
     */
    protected function getCreatePermissionActionClass(): string
    {
        $createPermissionAction = GeneratorHelper::createPermissionAction();

        if (! $createPermissionAction) {
            $createPermissionAction = $this->ask('Enter the class name of the "Create permission action"');

            if (empty($createPermissionAction)) {
                throw new LogicException('The "Create permission action" option is required');
            }
        }

        return $createPermissionAction;
    }

    /**
     * @return string
     */
    protected function getCreatePermissionDTOClass(): string
    {
        $createPermissionAction = GeneratorHelper::createPermissionDTO();

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
     *
     * @param string $namespace
     *
     * @return string
     */
    protected function convertNamespaceToPath(string $namespace): string
    {
        return trim(str_replace('\\', '/', $namespace), '/');
    }

    /**
     * Checks whether the given name is reserved.
     *
     * @param string $name
     * @return bool
     */
    protected function isReservedName(string $name): bool
    {
        $name = strtolower($name);

        return in_array($name, $this->reservedNames, true);
    }

    /**
     * Alphabetically sorts the imports for the given stub.
     *
     * @param string $stubContent
     *
     * @return string
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
