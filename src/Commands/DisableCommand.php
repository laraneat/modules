<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Module;

class DisableCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:disable
                            {module?* : Module name(s)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable the specified module(s) or all modules.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Disabling module ...');

        /** @var array<Module|string> $modulesToHandle */
        $modulesToHandle = $this->argument('module') ?: $this->modules->all();

        foreach($modulesToHandle as $module) {
            $this->disable($module);
        }

        return self::SUCCESS;
    }

    protected function disable(Module|string $moduleOrName): void
    {
        $module = $this->findModuleOrFail($moduleOrName);

        if ($module->isEnabled()) {
            $module->disable();

            $this->components->info("Module <info>{$module->getName()}</info> disabled successful.");
        } else {
            $this->components->warn("Module <info>{$module->getName()}</info> has already disabled.");
        }
    }
}
