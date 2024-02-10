<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Module;

class EnableCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:enable
                            {module?* : Module name(s)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable the specified module(s) or all modules.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Enabling module ...');

        /** @var array<Module|string> $modulesToHandle */
        $modulesToHandle = $this->argument('module') ?: $this->modules->allDisabled();

        foreach($modulesToHandle as $module) {
            $this->enable($module);
        }

        return self::SUCCESS;
    }

    public function enable(Module|string $moduleOrName): void
    {
        $module = $this->findModuleOrFail($moduleOrName);

        if ($module->isDisabled()) {
            $module->enable();

            $this->components->info("Module <info>{$module->getName()}</info> enabled successful.");
        }else {
            $this->components->warn("Module <info>{$module->getName()}</info> has already enabled.");
        }

    }
}
