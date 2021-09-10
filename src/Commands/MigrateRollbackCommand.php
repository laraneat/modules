<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Migrations\Migrator;
use Laraneat\Modules\Module;
use Laraneat\Modules\Traits\MigrationLoaderTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MigrateRollbackCommand extends Command
{
    use MigrationLoaderTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate-rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the modules migrations.';

    /**
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->repository = $this->laravel['modules'];

        $name = $this->argument('module');

        if (!empty($name)) {
            $this->rollback($name);

            return 0;
        }

        foreach ($this->repository->getOrdered($this->option('direction')) as $module) {
            $this->line('Running for module: <info>' . $module->getName() . '</info>');

            $this->rollback($module);
        }

        return 0;
    }

    /**
     * Rollback migration from the specified module.
     *
     * @param Module|string $module
     */
    public function rollback($module): void
    {
        if (is_string($module)) {
            $module = $this->repository->findOrFail($module);
        }

        $migrator = new Migrator($module, $this->getLaravel());

        $database = $this->option('database');

        if (!empty($database)) {
            $migrator->setDatabase($database);
        }

        $migrated = $migrator->rollback();

        if (count($migrated)) {
            foreach ($migrated as $migration) {
                $this->line("Rollback: <info>{$migration}</info>");
            }

            return;
        }

        $this->comment('Nothing to rollback.');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'desc'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
        ];
    }
}
