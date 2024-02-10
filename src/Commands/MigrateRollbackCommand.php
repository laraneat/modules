<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\ConfirmableTrait;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

class MigrateRollbackCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:migrate:rollback
                            {module?* : Module name(s)}
                            {--d|direction=asc : The direction of ordering (asc/desc)}
                            {--subpath=* : The subpath(s) to the migrations files to be executed}
                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                            {--database= : The database connection to use}
                            {--force : Force the operation to run when in production}
                            {--pretend : Dump the SQL queries that would be run}
                            {--step= : The number of migrations to be reverted}
                            {--batch= : The batch of migrations (identified by their batch number) to be reverted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the modules migrations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        /** @var array<Module|string> $modulesToHandle */
        $modulesToHandle = $this->argument('module') ?: $this->modules->getOrdered($this->option('direction') ?: 'asc');

        foreach($modulesToHandle as $module) {
            $this->rollback($module);
        }

        return self::SUCCESS;

    }

    /**
     * Rollback migration from the specified module.
     */
    protected function rollback(Module|string $moduleOrName): void
    {
        $module = $this->findModuleOrFail($moduleOrName);

        $this->line('Running for module: <info>' . $module->getName() . '</info>');

        $moduleMigrationPath = $module->getExtraPath(GeneratorHelper::component('migration')->getPath());

        $paths = $this->option('subpath')
            ? collect($this->option('subpath'))
                ->map(static fn (string $subPath) => $moduleMigrationPath . "/" .$subPath)->all()
            : [$moduleMigrationPath];

        $this->call('migrate:rollback', [
            '--path' => $paths,
            '--database' => $this->option('database'),
            '--step' => $this->option('step') ?: null,
            '--batch' => $this->option('batch') ?: null,
            '--realpath' => (bool) $this->option('realpath'),
            '--pretend' => (bool) $this->option('pretend'),
            '--force' => true,
        ]);
    }
}
