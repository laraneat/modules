<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Support\Str;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Stub;
use Laraneat\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputOption;

/**
 * @group generator
 */
class EventMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:make:event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new event for the specified module.';

    /**
     * Module instance.
     *
     * @var Module
     */
    protected Module $module;

    /**
     * Component type.
     *
     * @var string
     */
    protected string $componentType = 'event';

    /**
     * Prepared 'name' argument.
     *
     * @var string
     */
    protected string $nameArgument;

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['model', null, InputOption::VALUE_REQUIRED, 'The class name of the model to be used in the seeder.'],
        ];
    }

    protected function prepare()
    {
        $this->module = $this->getModule();
        $this->nameArgument = $this->getTrimmedArgument('name');
    }

    protected function getDestinationFilePath(): string
    {
        return $this->getComponentPath($this->module, $this->nameArgument, $this->componentType);
    }

    protected function getTemplateContents(): string
    {
        $model = $this->getOptionOrAsk(
            'model',
            'Enter the class name of the model to be used in the event',
            '',
            true
        );
        $modelClass = $this->getClass($model);
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace($this->module, $this->nameArgument, $this->componentType),
            'class' => $this->getClass($this->nameArgument),
            'model' => $modelClass,
            'modelEntity' => Str::camel($modelClass),
            'modelNamespace' => $this->getComponentNamespace($this->module, $model, 'model')
        ];

        return Stub::create("event.stub", $stubReplaces)->render();
    }
}
