<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class FactoryMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:factory
                            {name : The name of the factory class}
                            {module? : The name or package name of the app module}
                            {--model= : The class name of the model to be used in the factory}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new factory for the specified module.';

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType = ModuleComponentType::Factory;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the factory class name',
        ];
    }

    protected function getContents(): string
    {
        $modelClass = $this->getFullClassFromOptionOrAsk(
            optionName: 'model',
            question: 'Enter the class name of the model to be used in the factory',
            componentType: ModuleComponentType::Model,
            module: $this->module
        );
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace(
                $this->module,
                $this->nameArgument,
                $this->componentType
            ),
            'class' => class_basename($this->nameArgument),
            'model' => class_basename($modelClass),
            'modelCamelCase' => Str::camel(class_basename($modelClass)),
            'modelNamespace' => $this->getNamespaceOfClass($modelClass),
        ];

        return Stub::create("factory.stub", $stubReplaces)->render();
    }
}
