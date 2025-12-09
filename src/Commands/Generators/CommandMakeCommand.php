<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class CommandMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:command
                            {name : The name of the command class}
                            {module? : The name or package name of the app module}
                            {--s|signature= : The signature of the console command}
                            {--description= : The console command description}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new artisan command for the specified module.';

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType = ModuleComponentType::CliCommand;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the command class name',
        ];
    }

    protected function getContents(): string
    {
        $signature = $this->getOptionOrAsk(
            'signature',
            'Enter the console command signature that should be assigned'
        );
        $description = $this->getOptionOrAsk(
            'description',
            'Enter the console command description',
            '',
            false
        );
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace(
                $this->module,
                $this->nameArgument,
                $this->componentType
            ),
            'class' => class_basename($this->nameArgument),
            'signature' => $signature,
            'description' => $description,
        ];

        return Stub::create("command.stub", $stubReplaces)->render();
    }
}
