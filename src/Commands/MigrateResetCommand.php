<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Module;

class MigrateResetCommand extends BaseMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:migrate:reset
                            {module?* : Module name(s) or package name(s)}
                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                            {--database= : The database connection to use}
                            {--force : Force the operation to run when in production}
                            {--pretend : Dump the SQL queries that would be run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the modules migrations.';

    /**
     * Reset migration from the specified module.
     */
    protected function executeForModule(Module $module): void
    {
        $this->call('migrate:reset', [
            '--path' => $this->getMigrationPaths($module),
            '--database' => $this->option('database'),
            '--realpath' => (bool) $this->option('realpath'),
            '--pretend' => (bool) $this->option('pretend'),
            '--force' => true,
        ]);
    }
}
