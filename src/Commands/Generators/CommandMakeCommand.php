<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Support\Str;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Support\Config\GenerateConfigReader;
use Laraneat\Modules\Support\Stub;
use Laraneat\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CommandMakeCommand extends GeneratorCommand
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
    protected $name = 'module:make-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new Artisan command for the specified module.';

    public function getDefaultNamespace(): string
    {
        return Modules::config('paths.generator.command.namespace') ?: Modules::config('paths.generator.command.path', 'Console');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the command.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that should be assigned.', null],
        ];
    }

    protected function getTemplateContents(): string
    {
        $module = Modules::findOrFail($this->getModuleName());

        return (new Stub('/command.stub', [
            'COMMAND_NAME' => $this->getCommandName(),
            'NAMESPACE'    => $this->getClassNamespace($module),
            'CLASS'        => $this->getClass(),
        ]))->render();
    }

    private function getCommandName(): string
    {
        return $this->option('command') ?: 'command:name';
    }

    protected function getDestinationFilePath(): string
    {
        $path = Modules::getModulePath($this->getModuleName());

        $commandPath = GenerateConfigReader::read('command');

        return $path . $commandPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    private function getFileName(): string
    {
        return Str::studly($this->argument('name'));
    }
}
