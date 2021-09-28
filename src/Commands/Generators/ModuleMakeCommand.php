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
        $modelName = $this->option('model');

        $code = (new ModuleGenerator($name))
            ->setModelName($modelName ?: $name)
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
            return E_ERROR;
        }

        return 0;
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
            ['plain', 'p', InputOption::VALUE_NONE, 'Generate a plain module (without some components).'],
            ['disabled', 'd', InputOption::VALUE_NONE, 'Do not enable the module at creation.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when the module already exists.'],
            ['model', null, InputOption::VALUE_REQUIRED, 'The class name of the model.'],
        ];
    }

    /**
    * Get module type.
    *
    * @return string
    */
    private function getModuleType(): string
    {
        $isPlain = $this->option('plain');

        if ($isPlain) {
            return 'plain';
        }

        return 'full';
    }
}
