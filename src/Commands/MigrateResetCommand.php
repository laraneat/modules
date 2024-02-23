<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\ConfirmableTrait;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Module;

class MigrateResetCommand extends BaseCommand
{
    use ConfirmableTrait;

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
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        try {
            $modulesToHandle = $this->getModuleArgumentOrFail();
        } catch (ModuleNotFound $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach($modulesToHandle as $module) {
            $this->reset($module);
        }

        return self::SUCCESS;

    }

    /**
     * Reset migration from the specified module.
     */
    protected function reset(Module $module): void
    {
        $this->line('Running for module: <info>' . $module->getName() . '</info>');

        $this->call('migrate:reset', [
            '--path' => $module->getMigrationPaths(),
            '--database' => $this->option('database'),
            '--realpath' => (bool) $this->option('realpath'),
            '--pretend' => (bool) $this->option('pretend'),
            '--force' => true,
        ]);
    }
}
