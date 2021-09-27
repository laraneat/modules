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
class ActionMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:make:action';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new action for the specified module.';

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
            ['model', null, InputOption::VALUE_REQUIRED, 'The class name of the model to be used in the action.'],
            ['request', null, InputOption::VALUE_REQUIRED, 'The class name of the request to be used in the action.'],
            ['resource', null, InputOption::VALUE_REQUIRED, 'The class name of the resource to be used in the action.'],
            ['wizard', null, InputOption::VALUE_REQUIRED, 'The class name of the wizard to be used in the action.'],
        ];
    }

    protected function prepare()
    {
        $this->module = $this->getModule();
        $this->stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['plain', 'create', 'delete', 'list', 'update', 'view'],
            'plain'
        );
        $this->componentType = 'action';
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
            if ($this->stub !== 'delete') {
                $resource = $this->getOptionOrAsk(
                    'resource',
                    'Enter the class name of the resource to be used in the action',
                    '',
                    true
                );
                $stubReplaces['resource'] = $this->getClass($resource);
                $stubReplaces['resourceNamespace'] = $this->getComponentNamespace($this->module, $resource, 'api-resource');

                if (in_array($this->stub, ['list', 'view'], true)) {
                    $wizard = $this->getOptionOrAsk(
                        'wizard',
                        'Enter the class name of the wizard to be used in the action',
                        '',
                        true
                    );
                    $stubReplaces['queryWizard'] = $this->getClass($wizard);
                    $stubReplaces['queryWizardNamespace'] = $this->getComponentNamespace($this->module, $wizard, 'api-query-wizard');
                }
            }

            $model = $this->getOptionOrAsk(
                'model',
                'Enter the class name of the model to be used in the action',
                '',
                true
            );
            $stubReplaces['model'] = $this->getClass($model);
            $stubReplaces['modelEntity'] = Str::camel($stubReplaces['model']);
            $stubReplaces['modelNamespace'] = $this->getComponentNamespace($this->module, $model, 'model');

            $request = $this->getOptionOrAsk(
                'request',
                'Enter the class name of the request to be used in the action',
                '',
                true
            );
            $stubReplaces['request'] = $this->getClass($request);
            $stubReplaces['requestNamespace'] = $this->getComponentNamespace($this->module, $request, 'api-request');
        }

        return Stub::create("action/{$this->stub}.stub", $stubReplaces)->render();
    }
}
