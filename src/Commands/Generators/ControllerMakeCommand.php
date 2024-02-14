<?php

namespace Laraneat\Modules\Commands\Generators;

use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Stub;
use Laraneat\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputOption;

/**
 * @group generator
 */
class ControllerMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new controller for the specified module.';

    /**
     * The UI for which the request will be created.
     *
     * @var string
     */
    protected string $ui = 'api';

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
    protected string $componentType;

    /**
     * Prepared 'name' argument.
     *
     * @var string
     */
    protected string $nameArgument;

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['ui', null, InputOption::VALUE_REQUIRED, 'The UI for which the request will be created.'],
        ];
    }

    protected function prepare()
    {
        $this->module = $this->getModule();
        $this->ui = $this->getOptionOrChoice(
            'ui',
            'Select UI for which the request will be created',
            ['api', 'web'],
            'api'
        );
        $this->componentType = "{$this->ui}-controller";
        $this->nameArgument = $this->getTrimmedArgument('name');
    }

    protected function getDestinationFilePath(): string
    {
        return $this->getComponentPath($this->module, $this->nameArgument, $this->componentType);
    }

    protected function getTemplateContents(): string
    {
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace($this->module, $this->nameArgument, $this->componentType),
            'class' => $this->getClass($this->nameArgument),
        ];

        return Stub::create("controller/{$this->ui}.stub", $stubReplaces)->render();
    }
}
