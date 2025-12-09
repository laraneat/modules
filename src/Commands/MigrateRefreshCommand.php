<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\ConfirmableTrait;

class MigrateRefreshCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:migrate:refresh
                            {module?* : Module name(s) or package name(s)}
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
    protected $description = 'Rollback & re-migrate the modules migrations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $this->call('module:migrate:reset', [
            'module' => $this->argument('module'),
            '--realpath' => $this->option('realpath'),
            '--database' => $this->option('database'),
            '--pretend' => $this->option('pretend'),
            '--force' => true,
        ]);

        $this->call('module:migrate', [
            'module' => $this->argument('module'),
            '--realpath' => $this->option('realpath'),
            '--database' => $this->option('database'),
            '--schema-path' => $this->option('schema-path'),
            '--pretend' => $this->option('pretend'),
            '--seed' => $this->option('seed'),
            '--seeder' => $this->option('seeder'),
            '--step' => $this->option('step'),
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
