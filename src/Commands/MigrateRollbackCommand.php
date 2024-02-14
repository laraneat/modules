<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\ConfirmableTrait;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Module;

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

        try {
            $modulesToHandle = $this->getModuleArgumentOrFail();
        } catch (ModuleNotFoundException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach($modulesToHandle as $module) {
            $this->rollback($module);
        }

        return self::SUCCESS;

    }

    /**
     * Rollback migration from the specified module.
     */
    protected function rollback(Module $module): void
    {
        $this->line('Running for module: <info>' . $module->getName() . '</info>');

        $this->call('migrate:rollback', [
            '--path' => $module->getMigrationPaths(),
            '--database' => $this->option('database'),
            '--step' => $this->option('step') ?: null,
            '--batch' => $this->option('batch') ?: null,
            '--realpath' => (bool) $this->option('realpath'),
            '--pretend' => (bool) $this->option('pretend'),
            '--force' => true,
        ]);
    }
}
