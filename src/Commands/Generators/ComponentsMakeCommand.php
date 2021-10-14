<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Console\Command;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Generators\ModuleComponentsGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @group generator
 */
class ComponentsMakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make:components';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create module components.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $entityName = $this->option('entity');
        $module = Modules::findOrFail($name);

        $code = (new ModuleComponentsGenerator($module))
            ->setEntityName($entityName ?: $name)
            ->setType($this->getModuleType())
            ->setConsole($this)
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
            ['name', InputArgument::REQUIRED, 'The name of the module.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
//            ['api', 'a', InputOption::VALUE_NONE, 'Generate an api module (with api components, enabled by default).'],
//            ['web', 'w', InputOption::VALUE_NONE, 'Generate a web module (with web components).'],
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
//        if ($this->option('web')) {
//            return 'web';
//        }

        return 'api';
    }
}
