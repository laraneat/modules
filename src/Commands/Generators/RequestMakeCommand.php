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
class RequestMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:make:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new request for the specified module.';

    /**
     * The UI for which the request will be created.
     *
     * @var string
     */
    protected string $ui = 'api';

    /**
     * The stub name to load for this generator
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
            ['ui', null, InputOption::VALUE_REQUIRED, 'The UI for which the request will be created.'],
            ['stub', 's', InputOption::VALUE_REQUIRED, 'The stub name to load for this generator.'],
            ['dto', null, InputOption::VALUE_REQUIRED, 'The class name of the DTO to be used in the request.'],
            ['model', null, InputOption::VALUE_REQUIRED, 'The class name of the model to be used in the request.'],
        ];
    }

    protected function prepare()
    {
        $this->module = $this->getModule();
        $this->ui = $this->getOptionOrChoice(
            'ui',
            'Select the UI for which the request will be created',
            ['api', 'web'],
            'api'
        );
        $stubChoices = ($this->ui === "web")
            ? ['plain', 'create', 'delete', 'update']
            : ['plain', 'create', 'delete', 'list', 'update', 'view'];
        $this->stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            $stubChoices,
            'plain'
        );
        $this->componentType = "{$this->ui}-request";
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

        if ($this->stub !== 'plain') {
            if ($this->stub === 'create' || $this->stub === 'update') {
                $dto = $this->getOptionOrAsk(
                    'dto',
                    'Enter the class name of the DTO to be used in the request',
                    '',
                    true
                );
                $stubReplaces['dto'] = $this->getClass($dto);
                $stubReplaces['dtoNamespace'] = $this->getComponentNamespace($this->module, $dto, 'dto');
            }

            $model = $this->getOptionOrAsk(
                'model',
                'Enter the class name of the model to be used in the request',
                '',
                true
            );
            $stubReplaces['model'] = $this->getClass($model);
            $stubReplaces['modelEntity'] = Str::camel($stubReplaces['model']);
            $stubReplaces['modelNamespace'] = $this->getComponentNamespace($this->module, $model, 'model');
        }

        return Stub::create("request/{$this->stub}.stub", $stubReplaces)->render();
    }
}
