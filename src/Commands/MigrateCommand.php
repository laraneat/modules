<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\ConfirmableTrait;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Module;

class MigrateCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:migrate
                            {module?* : Module name(s)}
                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                            {--database= : The database connection to use}
                            {--force : Force the operation to run when in production}
                            {--schema-path= : The path to a schema dump file}
                            {--pretend : Dump the SQL queries that would be run}
                            {--seed : Indicates if the seed task should be re-run}
                            {--seeder= : The class name of the root seeder}
                            {--step : Force the migrations to be run so they can be rolled back individually}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate the migrations from the specified module(s) or from all modules.';

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
            $this->migrate($module);
        }

        return self::SUCCESS;
    }

    /**
     * Run the migration from the specified module.
     */
    protected function migrate(Module $module): void
    {
        $this->line('Running for module: <info>' . $module->getPackageName() . '</info>');

        $this->call('migrate', [
            '--path' => $module->getMigrationPaths(),
            '--realpath' => (bool) $this->option('realpath'),
            '--database' => $this->option('database'),
            '--schema-path' => $this->option('schema-path'),
            '--pretend' => (bool) $this->option('pretend'),
            '--step' => (bool) $this->option('step'),
            '--force' => true,
        ]);

        if ($this->option('seed') && ! $this->option('pretend')) {
            $this->call('module:seed', [
                'module' => $module->getName(),
                '--class' => $this->option('seeder'),
                '--force' => true,
            ]);
        }
    }
}
