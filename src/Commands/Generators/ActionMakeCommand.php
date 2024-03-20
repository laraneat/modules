<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Exceptions\ModuleHasNoNamespace;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Exceptions\NameIsReserved;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class ActionMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:action
                            {name : The name of the action class}
                            {module? : The name or package name of the app module}
                            {--s|stub= : The stub name to load for this generator}
                            {--dto= : The class name of the DTO to be used in the action}
                            {--model= : The class name of the model to be used in the action}
                            {--request= : The class name of the request to be used in the action}
                            {--resource= : The class name of the resource to be used in the action}
                            {--wizard= : The class name of the query wizard to be used in the action}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new action class for the specified module.';

    /**
     * The module instance
     *
     * @var Module
     */
    protected Module $module;

    /**
     * The 'name' argument
     */
    protected string $nameArgument;

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType = ModuleComponentType::Action;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the action class name',
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->ensurePackageIsInstalledOrWarn('lorisleiva/laravel-actions');

        try {
            $this->nameArgument = $this->argument('name');
            $this->ensureNameIsNotReserved($this->nameArgument);
            $this->module = $this->getModuleArgumentOrFail();
        } catch (NameIsReserved|ModuleNotFound|ModuleHasNonUniquePackageName|ModuleHasNoNamespace $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return $this->generate(
            $this->getComponentPath($this->module, $this->nameArgument, $this->componentType),
            $this->getContents(),
            $this->option('force')
        );
    }

    protected function getContents(): string
    {
        $stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['plain', 'create', 'delete', 'list', 'update', 'view'],
            'plain'
        );

        return Stub::create("action/$stub.stub", $this->getStubReplaces($stub))->render();
    }

    /**
     * @param string $stub
     * @return array<string, string>
     */
    protected function getStubReplaces(string $stub): array
    {
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace(
                $this->module,
                $this->nameArgument,
                $this->componentType
            ),
            'class' => class_basename($this->nameArgument),
        ];

        if ($stub === 'plain') {
            return $stubReplaces;
        }

        if ($stub === 'create' || $stub === 'update') {
            $dtoClass = $this->getFullClassFromOptionOrAsk(
                optionName: 'dto',
                question: 'Enter the class name of the DTO to be used in the action',
                componentType: ModuleComponentType::Dto,
                module: $this->module
            );
            $stubReplaces['dto'] = class_basename($dtoClass);
            $stubReplaces['dtoCamelCase'] = Str::camel($stubReplaces['dto']);
            $stubReplaces['dtoNamespace'] = $this->getNamespaceOfClass($dtoClass);
        }

        if ($stub !== 'delete') {
            $resourceClass = $this->getFullClassFromOptionOrAsk(
                optionName: 'resource',
                question: 'Enter the class name of the resource to be used in the action',
                componentType: ModuleComponentType::ApiResource,
                module: $this->module
            );
            $stubReplaces['resource'] = class_basename($resourceClass);
            $stubReplaces['resourceNamespace'] = $this->getNamespaceOfClass($resourceClass);

            if (in_array($stub, ['list', 'view'], true)) {
                $wizardClass = $this->getFullClassFromOptionOrAsk(
                    optionName: 'wizard',
                    question: 'Enter the class name of the query wizard to be used in the action',
                    componentType: ModuleComponentType::ApiQueryWizard,
                    module: $this->module
                );
                $stubReplaces['queryWizard'] = class_basename($wizardClass);
                $stubReplaces['queryWizardNamespace'] = $this->getNamespaceOfClass($wizardClass);
            }
        }

        $modelClass = $this->getFullClassFromOptionOrAsk(
            optionName: 'model',
            question: 'Enter the class name of the model to be used in the action',
            componentType: ModuleComponentType::Model,
            module: $this->module
        );
        $stubReplaces['model'] = class_basename($modelClass);
        $stubReplaces['modelCamelCase'] = Str::camel($stubReplaces['model']);
        $stubReplaces['modelNamespace'] = $this->getNamespaceOfClass($modelClass);

        $requestClass = $this->getFullClassFromOptionOrAsk(
            optionName: 'request',
            question: 'Enter the class name of the request to be used in the action',
            componentType: ModuleComponentType::ApiRequest,
            module: $this->module
        );
        $stubReplaces['request'] = class_basename($requestClass);
        $stubReplaces['requestNamespace'] = $this->getNamespaceOfClass($requestClass);

        return $stubReplaces;
    }
}
