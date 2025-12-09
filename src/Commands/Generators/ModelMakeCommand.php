<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class ModelMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:model
                            {name : The name of the model}
                            {module? : The name or package name of the app module}
                            {--factory= : The class name of the model factory}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new model for the specified module.';

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType = ModuleComponentType::Model;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the model class name',
        ];
    }

    protected function getContents(): string
    {
        $stub = 'plain';
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace($this->module, $this->nameArgument, $this->componentType),
            'class' => class_basename($this->nameArgument),
        ];

        $factoryOption = $this->getOptionOrAsk(
            'factory',
            question: 'Enter the class name of the factory to be used in the model (optional)',
            required: false
        );

        if ($factoryOption) {
            $factoryClass = $this->getFullClass(
                $factoryOption,
                GeneratorHelper::component(ModuleComponentType::Factory)
                    ->getFullNamespace($this->module)
            );

            $stub = 'full';
            $stubReplaces['factory'] = class_basename($factoryClass);
            $stubReplaces['factoryNamespace'] = $this->getNamespaceOfClass($factoryClass);
        }

        return Stub::create("model/$stub.stub", $stubReplaces)->render();
    }
}
