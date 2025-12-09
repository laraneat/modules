<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class ControllerMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:controller
                            {name : The name of the controller class}
                            {module? : The name or package name of the app module}
                            {--ui= : The UI for which the controller will be created}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new controller for the specified module.';

    /**
     * The UI for which the controller will be created.
     * ('web' or 'api')
     */
    protected string $ui;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the controller class name',
        ];
    }

    protected function beforeGenerate(): void
    {
        $this->ui = $this->getOptionOrChoice(
            'ui',
            question: 'Enter the UI for which the controller will be created',
            choices: ['api', 'web'],
            default: 'api'
        );
        $this->componentType = $this->ui === 'api'
            ? ModuleComponentType::ApiController
            : ModuleComponentType::WebController;
    }

    protected function getContents(): string
    {
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace($this->module, $this->nameArgument, $this->componentType),
            'class' => class_basename($this->nameArgument),
        ];

        return Stub::create("controller/{$this->ui}.stub", $stubReplaces)->render();
    }
}
