<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Module;

class MigrateRollbackCommand extends BaseMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:migrate:rollback
                            {module?* : Module name(s) or package name(s)}
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
     * Rollback migration from the specified module.
     */
    protected function executeForModule(Module $module): void
    {
        $this->call('migrate:rollback', [
            '--path' => $this->getMigrationPaths($module),
            '--database' => $this->option('database'),
            '--step' => $this->option('step') ?: null,
            '--batch' => $this->option('batch') ?: null,
            '--realpath' => (bool) $this->option('realpath'),
            '--pretend' => (bool) $this->option('pretend'),
            '--force' => true,
        ]);
    }
}
