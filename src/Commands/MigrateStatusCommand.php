<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Migrations\Migrator;
use Laraneat\Modules\Module;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MigrateStatusCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Status for all module migrations';

    /**
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $this->repository = $this->laravel['modules'];

        $name = $this->argument('module');

        if ($name) {
            $module = $this->repository->findOrFail($name);

            $this->migrateStatus($module);

            return 0;
        }

        foreach ($this->repository->getOrdered($this->option('direction')) as $module) {
            $this->line('Running for module: <info>' . $module->getName() . '</info>');
            $this->migrateStatus($module);
        }

        return 0;
    }

    /**
     * Run the migration from the specified module.
     *
     * @param Module $module
     */
    protected function migrateStatus(Module $module): void
    {
        $path = str_replace(base_path(), '', (new Migrator($module, $this->getLaravel()))->getPath());

        $this->call('migrate:status', [
            '--path' => $path,
            '--database' => $this->option('database'),
        ]);
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
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
        ];
    }
}
