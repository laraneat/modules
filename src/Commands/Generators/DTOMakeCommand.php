<?php

namespace Laraneat\Modules\Commands\Generators;

use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Stub;
use Laraneat\Modules\Traits\ModuleCommandTrait;

/**
 * @group generator
 */
class DTOMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:make:dto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new DTO for the specified module.';

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
    protected string $componentType = 'dto';

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
        return [];
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
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace($this->module, $this->nameArgument, $this->componentType),
            'class' => $this->getClass($this->nameArgument)
        ];

        return Stub::create("dto/default.stub", $stubReplaces)->render();
    }
}
