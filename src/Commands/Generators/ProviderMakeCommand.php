<?php

namespace Laraneat\Modules\Commands\Generators;

use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Laraneat\Modules\Support\Stub;
use Laraneat\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputOption;

/**
 * @group generator
 */
class ProviderMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:make:provider';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new provider for the specified module.';

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
     *
     * @return array
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
            ['plain', 'module', 'route', 'event'],
            'plain'
        );
        $this->componentType = 'provider';
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

        if ($this->stub === 'module') {
            $stubReplaces = array_merge($stubReplaces, [
                'name' => $this->module->getStudlyName(),
                'lowerName' => $this->module->getLowerName(),
                'commandsPath' => GeneratorHelper::component('cli-command')->getPath(),
                'langPath' => GeneratorHelper::component('lang')->getPath(),
                'configPath' => GeneratorHelper::component('config')->getPath(),
                'viewsPath' => GeneratorHelper::component('view')->getPath(),
                'migrationsPath' => GeneratorHelper::component('migration')->getPath(),
            ]);
        } elseif ($this->stub === 'route') {
            $stubReplaces = array_merge($stubReplaces, [
                'name' => $this->module->getStudlyName(),
                'webControllerNamespace' => str_replace('\\', '\\\\', GeneratorHelper::component('web-controller')->getFullNamespace($this->module)),
                'apiControllerNamespace' => str_replace('\\', '\\\\', GeneratorHelper::component('api-controller')->getFullNamespace($this->module)),
                'webRoutesPath' => GeneratorHelper::component('web-route')->getPath(),
                'apiRoutesPath' => GeneratorHelper::component('api-route')->getPath()
            ]);
        }

        return Stub::create("provider/{$this->stub}.stub", $stubReplaces)->render();
    }
}
