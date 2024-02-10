<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Module;

class DumpCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:dump
                            {module?* : Module name(s)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump-autoload the specified module(s) or for all module.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Generating optimized autoload modules.');

        /** @var array<Module|string> $modulesToHandle */
        $modulesToHandle = $this->argument('module') ?: $this->modules->all();

        foreach($modulesToHandle as $module) {
            $this->dump($module);
        }

        return self::SUCCESS;
    }

    protected function dump(Module|string $moduleOrName): void
    {
        $module = $this->findModuleOrFail($moduleOrName);

        $this->components->task("$module", function () use ($module) {
            chdir($module->getPath());

            passthru('composer dump -o -n -q');
        });
    }
}
