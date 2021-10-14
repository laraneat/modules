<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Traits\ConsoleHelpersTrait;
use Symfony\Component\Console\Input\InputArgument;

class UpdateCommand extends Command
{
    use ConsoleHelpersTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update dependencies for the specified module or for all modules.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('module');

        if ($name) {
            $this->updateModule($name);

            return self::SUCCESS;
        }

        foreach (Modules::getOrdered() as $module) {
            $this->updateModule($module->getName());
        }

        return self::SUCCESS;
    }

    protected function updateModule(string $moduleName): void
    {
        $this->line('Running for module: <info>' . $moduleName . '</info>');

        Modules::update($moduleName);

        $this->info("Module [{$moduleName}] updated successfully.");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be updated.'],
        ];
    }
}
