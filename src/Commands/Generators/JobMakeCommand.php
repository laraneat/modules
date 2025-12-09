<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class JobMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:job
                            {name : The name of the job class}
                            {module? : The name or package name of the app module}
                            {--s|stub= : The stub name to load for this generator}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new job class for the specified module.';

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType = ModuleComponentType::Job;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the job class name',
        ];
    }

    protected function getContents(): string
    {
        $stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['plain', 'queued'],
            'plain'
        );
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace(
                $this->module,
                $this->nameArgument,
                $this->componentType
            ),
            'class' => class_basename($this->nameArgument),
        ];

        return Stub::create("job/$stub.stub", $stubReplaces)->render();
    }
}
