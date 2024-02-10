<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

class MigrateStatusCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:migrate:status
                            {module?* : Module name(s)}
                            {--d|direction=asc : The direction of ordering (asc/desc)}
                            {--subpath=* : The subpath(s) to the migrations files to be executed}
                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                            {--database= : The database connection to use}
                            {--pending : Only list pending migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the modules migrations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var array<Module|string> $modulesToHandle */
        $modulesToHandle = $this->argument('module') ?: $this->modules->getOrdered($this->option('direction') ?: 'asc');

        foreach($modulesToHandle as $module) {
            $this->status($module);
        }

        return self::SUCCESS;
    }

    /**
     * Show migration status from the specified module.
     */
    protected function status(Module|string $moduleOrName): void
    {
        $module = $this->findModuleOrFail($moduleOrName);

        $this->line('Running for module: <info>' . $module->getName() . '</info>');

        $moduleMigrationPath = $module->getExtraPath(GeneratorHelper::component('migration')->getPath());

        $paths = $this->option('subpath')
            ? collect($this->option('subpath'))
                ->map(static fn (string $subPath) => $moduleMigrationPath . "/" .$subPath)->all()
            : [$moduleMigrationPath];

        $this->call('migrate:status', [
            '--path' => $paths,
            '--database' => $this->option('database'),
            '--realpath' => (bool) $this->option('realpath'),
            '--pending' => (bool) $this->option('pending'),
        ]);
    }
}
