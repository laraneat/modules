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
class PolicyMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:policy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new policy for the specified module.';

    /**
     * The stub name to load for this generator.
     *
     * @var string
     */
    protected string $stub = 'full';

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
            ['model', null, InputOption::VALUE_REQUIRED, 'The class name of the model.'],
        ];
    }

    protected function prepare()
    {
        $this->module = $this->getModule();
        $this->stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['plain', 'full'],
            'full'
        );
        $this->componentType = 'policy';
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

        if ($this->stub === 'full') {
            $model = $this->getOptionOrAsk(
                'model',
                'Enter the class name of the model',
                '',
                true
            );

            $stubReplaces['model'] = $this->getClass($model);
            $stubReplaces['modelEntity'] = Str::camel($stubReplaces['model']);
            if ($stubReplaces['modelEntity'] === 'user') {
                $stubReplaces['modelEntity'] = 'model';
            }
            $stubReplaces['modelPermissionEntity'] = Str::snake($stubReplaces['model'], '-');
            $stubReplaces['modelPermissionEntities'] = Str::snake(Str::plural($stubReplaces['model']), '-');
            $stubReplaces['modelNamespace'] = $this->getComponentNamespace($this->module, $model, 'model');

            $fullUserClass = $this->getUserModelClass();

            $stubReplaces['user'] = $this->getClass($fullUserClass);
            $stubReplaces['userNamespace'] = $this->getNamespaceOfClass($fullUserClass);
        }

        return Stub::create("policy/{$this->stub}.stub", $stubReplaces)->render();
    }
}
