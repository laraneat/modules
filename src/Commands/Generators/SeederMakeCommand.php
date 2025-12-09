<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class SeederMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:seeder
                            {name : The name of the seeder}
                            {module? : The name or package name of the app module}
                            {--s|stub= : The stub name to load for this generator}
                            {--model= : The class name of the model to be used in the seeder}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new seeder for the specified module.';

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType = ModuleComponentType::Seeder;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the seeder class name',
        ];
    }

    protected function getContents(): string
    {
        $stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['plain', 'permissions'],
            'plain'
        );
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace($this->module, $this->nameArgument, $this->componentType),
            'class' => class_basename($this->nameArgument),
        ];

        if ($stub === 'permissions') {
            $createPermissionActionClass = $this->getCreatePermissionActionClass();
            $stubReplaces['createPermissionAction'] = class_basename($createPermissionActionClass);
            $stubReplaces['createPermissionActionNamespace'] = $this->getNamespaceOfClass($createPermissionActionClass);

            $createPermissionDTOClass = $this->getCreatePermissionDTOClass();
            $stubReplaces['createPermissionDTO'] = class_basename($createPermissionDTOClass);
            $stubReplaces['createPermissionDTONamespace'] = $this->getNamespaceOfClass($createPermissionDTOClass);

            $modelClass = $this->getFullClassFromOptionOrAsk(
                optionName: 'model',
                question: 'Enter the class name of the model to be used in the seeder',
                componentType: ModuleComponentType::Model,
                module: $this->module
            );

            $stubReplaces['modelKebabCase'] = Str::kebab(class_basename($modelClass));
            $stubReplaces['modelsKebabCase'] = Str::plural($stubReplaces['modelKebabCase']);
        }

        return Stub::create("seeder/$stub.stub", $stubReplaces)->render();
    }
}
