<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Module;

class MigrateStatusCommand extends BaseMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:migrate:status
                            {module?* : Module name(s) or package name(s)}
                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                            {--database= : The database connection to use}
                            {--pending : Only list pending migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of the modules migrations.';

    /**
     * Whether to require confirmation in production.
     */
    protected bool $requiresConfirmation = false;

    /**
     * Show migration status from the specified module.
     */
    protected function executeForModule(Module $module): void
    {
        $this->call('migrate:status', [
            '--path' => $this->getMigrationPaths($module),
            '--database' => $this->option('database'),
            '--realpath' => (bool) $this->option('realpath'),
            '--pending' => (bool) $this->option('pending'),
        ]);
    }
}
