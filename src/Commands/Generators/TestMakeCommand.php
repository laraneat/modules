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
class TestMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:make:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new test for the specified module.';

    /**
     * The type of test to be created.
     *
     * @var string
     */
    protected string $type = 'unit';

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
            ['type', 't', InputOption::VALUE_REQUIRED, 'The type of test to be created.'],
            ['stub', 's', InputOption::VALUE_REQUIRED, 'The stub name to load for this generator.'],
            ['model', null, InputOption::VALUE_REQUIRED, 'The class name of the model to be used in the test.'],
            ['route', null, InputOption::VALUE_REQUIRED, 'The route name for HTTP tests.'],
        ];
    }

    protected function prepare()
    {
        $this->module = $this->getModule();
        $this->type = $this->getOptionOrChoice(
            'type',
            'Select the type of test to be created',
            ['unit', 'feature', 'api', 'web', 'cli'],
            'unit'
        );

        $stubsMap = [
            'api' => ['plain', 'create', 'delete', 'list', 'update', 'view'],
            'web' => ['plain', 'create', 'delete', 'update'],
            'cli' => ['plain'],
            'unit' => ['plain'],
            'feature' => ['plain']
        ];
        $stubChoices = $stubsMap[$this->type];
        if (count($stubChoices) === 1) {
            $this->stub = $stubChoices[0];
        } else {
            $this->stub = $this->getOptionOrChoice(
                'stub',
                'Select the stub you want to use for generator',
                $stubChoices,
                'plain'
            );
        }

        $this->componentType = "{$this->type}-test";
        $this->nameArgument = $this->getTrimmedArgument('name');
    }

    protected function getDestinationFilePath(): string
    {
        return $this->getComponentPath($this->module, $this->nameArgument, $this->componentType);
    }

    protected function getTemplateContents(): string
    {
        $stubReplaces = [
            'moduleKey' => $this->module->getKey(),
            'namespace' => $this->getComponentNamespace($this->module, $this->nameArgument, $this->componentType),
            'class' => $this->getClass($this->nameArgument)
        ];

        if ($this->stub !== 'plain') {
            $model = $this->getOptionOrAsk(
                'model',
                'Enter the class name of the model to be used in the test',
                '',
                true
            );
            $stubReplaces['model'] = $this->getClass($model);
            $stubReplaces['modelSnake'] = Str::snake($stubReplaces['model']);
            $stubReplaces['models'] = Str::plural($stubReplaces['model']);
            $stubReplaces['modelsSnake'] = Str::snake($stubReplaces['models']);
            $stubReplaces['modelEntity'] = Str::camel($stubReplaces['model']);
            $stubReplaces['modelNamespace'] = $this->getComponentNamespace($this->module, $model, 'model');
            $stubReplaces['modelPermissionEntity'] = Str::snake($stubReplaces['model'], '-');
            $stubReplaces['modelPermissionEntities'] = Str::plural($stubReplaces['modelPermissionEntity']);
        }

        if (in_array($this->type, ['api', 'web'])) {
            $stubReplaces['routeName'] = $this->getOptionOrAsk(
                'route',
                'Enter the route name for HTTP tests',
                '',
                true
            );
        }

        return Stub::create("test/{$this->type}/{$this->stub}.stub", $stubReplaces)->render();
    }
}
