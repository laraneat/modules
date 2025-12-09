<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class QueryWizardMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:query-wizard
                            {name : The name of the query-wizard class}
                            {module? : The name or package name of the app module}
                            {--s|stub= : The stub name to load for this generator}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new query-wizard class for the specified module.';

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType = ModuleComponentType::ApiQueryWizard;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the query-wizard class name',
        ];
    }

    protected function beforeGenerate(): void
    {
        $this->ensurePackageIsInstalledOrWarn('jackardios/laravel-query-wizard');
    }

    protected function getContents(): string
    {
        $stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['eloquent', 'model'],
            'eloquent'
        );

        $stubReplaces = [
            'namespace' => $this->getComponentNamespace(
                $this->module,
                $this->nameArgument,
                $this->componentType
            ),
            'class' => class_basename($this->nameArgument),
        ];

        return Stub::create("query-wizard/$stub.stub", $stubReplaces)->render();
    }
}
