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
class SeederMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:make:seeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new seeder for the specified module.';

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
            ['model', null, InputOption::VALUE_REQUIRED, 'The class name of the model to be used in the seeder.'],
        ];
    }

    protected function prepare()
    {
        $this->module = $this->getModule();
        $this->stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['plain', 'permissions'],
            'plain'
        );
        $this->componentType = 'seeder';
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

        if ($this->stub === 'permissions') {
            $createPermissionAction = $this->getCreatePermissionActionClass();
            $stubReplaces['createPermissionAction'] = $this->getClass($createPermissionAction);
            $stubReplaces['actionNamespace'] = $this->getNamespaceOfClass($createPermissionAction);

            $createPermissionDTO = $this->getCreatePermissionDTOClass();
            $stubReplaces['createPermissionDTO'] = $this->getClass($createPermissionDTO);
            $stubReplaces['dtoNamespace'] = $this->getNamespaceOfClass($createPermissionDTO);

            $model = $this->getOptionOrAsk(
                'model',
                'Enter the class name of the model to be used in the seeder',
                '',
                true
            );

            $modelClass = $this->getClass($model);
            $stubReplaces['modelPermissionEntity'] = Str::snake($modelClass, '-');
            $stubReplaces['modelPermissionEntities'] = Str::plural($stubReplaces['modelPermissionEntity']);
        }

        return Stub::create("seeder/{$this->stub}.stub", $stubReplaces)->render();
    }
}
