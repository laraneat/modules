<?php

namespace Laraneat\Modules\Commands\Generators;

use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Stub;
use Laraneat\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputOption;

/**
 * @group generator
 */
class MailMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new mail for the specified module.';

    /**
     * The stub name to load for this generator.
     *
     * @var string
     */
    protected string $stub = 'plain';

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
            ['stub', 's', InputOption::VALUE_REQUIRED, 'The stub name to load for this generator.'],
        ];
    }

    protected function prepare()
    {
        $this->module = $this->getModule();
        $this->stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['plain', 'queued'],
            'plain'
        );
        $this->componentType = 'mail';
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

        return Stub::create("mail/{$this->stub}.stub", $stubReplaces)->render();
    }
}
