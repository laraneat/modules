<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Console\Command;
use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\Generators\ModuleGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @group generator
 */
class ModuleMakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $entityName = $this->option('entity');

        $code = (new ModuleGenerator($name))
            ->setEntityName($entityName ?: $name)
            ->setFilesystem($this->laravel['files'])
            ->setRepository($this->laravel['modules'])
            ->setConfig($this->laravel['config'])
            ->setActivator($this->laravel[ActivatorInterface::class])
            ->setConsole($this)
            ->setForce($this->option('force'))
            ->setType($this->getModuleType())
            ->setActive(!$this->option('disabled'))
            ->generate();

        if ($code === E_ERROR) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the module to be created.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['api', 'a', InputOption::VALUE_NONE, 'Generate an api module (with api components, enabled by default).'],
            ['web', 'w', InputOption::VALUE_NONE, 'Generate a web module (with web components).'],
            ['plain', 'p', InputOption::VALUE_NONE, 'Generate a plain module (without some components).'],
            ['disabled', 'd', InputOption::VALUE_NONE, 'Do not enable the module at creation.'],
            ['force', 'f', InputOption::VALUE_NONE, 'Force the operation to run when the module already exists.'],
            ['entity', null, InputOption::VALUE_REQUIRED, 'Entity name (used to create module components, the default is the name of the module).'],
        ];
    }

    /**
    * Get module type.
    *
    * @return string
    */
    private function getModuleType(): string
    {
        if ($this->option('plain')) {
            return 'plain';
        }

        if ($this->option('web')) {
            return 'web';
        }

        return 'api';
    }
}
