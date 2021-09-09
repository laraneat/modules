<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Support\Str;
use Laraneat\Modules\Support\Config\GenerateConfigReader;
use Laraneat\Modules\Support\Stub;
use Laraneat\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class RuleMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument name.
     *
     * @var string
     */
    protected string $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-rule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new validation rule for the specified module.';

    public function getDefaultNamespace(): string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.rules.namespace') ?: $module->config('paths.generator.rules.path', 'Rules');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the rule class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * @return string
     */
    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/rule.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS'     => $this->getFileName(),
        ]))->render();
    }

    /**
     * @return string
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $rulePath = GenerateConfigReader::read('rules');

        return $path . $rulePath->getPath() . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    private function getFileName(): string
    {
        return Str::studly($this->argument('name'));
    }
}
