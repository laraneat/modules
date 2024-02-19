<?php

namespace Laraneat\Modules\Commands\Generators;

use Exception;
use Illuminate\Support\Str;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class ActionMakeCommand extends BaseComponentGeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:action
                            {name : The name of the component}
                            {module? : The name of the module}
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
    protected $description = 'Generate new action for the specified module.';

    /**
     * The module instance
     *
     * @var Module
     */
    protected Module $module;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $name = $this->argument('name');
            $this->ensureNameIsNotReserved($name);

            $this->module = $this->getModuleArgumentOrFail();
            $this->generate(
                $this->getComponentPath($this->module, $name, 'action'),
                $this->getContents(),
                $this->option('force')
            );
        } catch (Exception $exception) {
            $this->components->error($exception->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
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
        $name = $this->argument('name');

        $stubReplaces = [
            'namespace' => $this->getComponentNamespace($this->module, $name, 'action'),
            'class' => class_basename($name),
        ];

        if ($stub === 'plain') {
            return $stubReplaces;
        }

        if ($stub === 'create' || $stub === 'update') {
            $dtoClass = $this->getFullClassFromOptionOrAsk(
                optionName: 'dto',
                question: 'Enter the class name of the DTO to be used in the action',
                componentType: 'dto',
                module: $this->module
            );
            $stubReplaces['dto'] = class_basename($dtoClass);
            $stubReplaces['dtoEntity'] = Str::camel($stubReplaces['dto']);
            $stubReplaces['dtoNamespace'] = $this->getNamespaceOfClass($dtoClass);
        }

        if ($stub !== 'delete') {
            $resourceClass = $this->getFullClassFromOptionOrAsk(
                optionName: 'resource',
                question: 'Enter the class name of the resource to be used in the action',
                componentType: 'api-resource',
                module: $this->module
            );
            $stubReplaces['resource'] = class_basename($resourceClass);
            $stubReplaces['resourceNamespace'] = $this->getNamespaceOfClass($resourceClass);

            if (in_array($stub, ['list', 'view'], true)) {
                $wizardClass = $this->getFullClassFromOptionOrAsk(
                    optionName: 'wizard',
                    question: 'Enter the class name of the query wizard to be used in the action',
                    componentType: 'api-query-wizard',
                    module: $this->module
                );
                $stubReplaces['queryWizard'] = class_basename($wizardClass);
                $stubReplaces['queryWizardNamespace'] = $this->getNamespaceOfClass($wizardClass);
            }
        }

        $modelClass = $this->getFullClassFromOptionOrAsk(
            optionName: 'model',
            question: 'Enter the class name of the model to be used in the action',
            componentType: 'model',
            module: $this->module
        );
        $stubReplaces['model'] = class_basename($modelClass);
        $stubReplaces['modelEntity'] = Str::camel($stubReplaces['model']);
        $stubReplaces['modelNamespace'] = $this->getNamespaceOfClass($modelClass);

        $requestClass = $this->getFullClassFromOptionOrAsk(
            optionName: 'request',
            question: 'Enter the class name of the request to be used in the action',
            componentType: 'api-request',
            module: $this->module
        );
        $stubReplaces['request'] = class_basename($requestClass);
        $stubReplaces['requestNamespace'] = $this->getNamespaceOfClass($requestClass);

        return $stubReplaces;
    }
}
