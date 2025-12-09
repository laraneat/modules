<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class MiddlewareMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:middleware
                            {name : The name of the middleware class}
                            {module? : The name or package name of the app module}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new middleware class for the specified module.';

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType = ModuleComponentType::Middleware;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the middleware class name',
        ];
    }

    protected function getContents(): string
    {
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace(
                $this->module,
                $this->nameArgument,
                $this->componentType
            ),
            'class' => class_basename($this->nameArgument),
        ];

        return Stub::create("middleware.stub", $stubReplaces)->render();
    }
}
