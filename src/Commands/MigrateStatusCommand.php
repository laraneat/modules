<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Module;

class MigrateStatusCommand extends BaseCommand
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
    protected $description = 'Reset the modules migrations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $modulesToHandle = $this->getModuleArgumentOrFail();
        } catch (ModuleNotFoundException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach($modulesToHandle as $module) {
            $this->status($module);
        }

        return self::SUCCESS;
    }

    /**
     * Show migration status from the specified module.
     */
    protected function status(Module|string $module): void
    {
        $this->line('Running for module: <info>' . $module->getName() . '</info>');

        $this->call('migrate:status', [
            '--path' => $module->getMigrationPaths(),
            '--database' => $this->option('database'),
            '--realpath' => (bool) $this->option('realpath'),
            '--pending' => (bool) $this->option('pending'),
        ]);
    }
}
