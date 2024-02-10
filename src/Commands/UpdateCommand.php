<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Module;
use Laraneat\Modules\Traits\ModuleCommandTrait;

class UpdateCommand extends BaseCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:update';

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
        $this->components->info('Updating module ...');

        if ($name = $this->argument('module')) {
            $this->updateModule($name);

            return self::SUCCESS;
        }

        $this->updateAllModule();

        return self::SUCCESS;
    }


    protected function updateAllModule(): void
    {
        $modules = $this->modules->getOrdered();

        foreach ($modules as $module) {
            $this->updateModule($module);
        }

    }

    protected function updateModule(Module|string $module): void
    {

        if ($name instanceof Module) {
            $module = $name;
        }else {
            $module = $this->modules->findOrFail($name);
        }

        $this->components->task("Updating {$module->getName()} module", function () use ($module) {
            $this->modules->update($module);
        });
        $this->modules->update($name);

    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be updated.'],
        ];
    }
}
