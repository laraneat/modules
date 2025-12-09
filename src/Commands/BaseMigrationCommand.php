<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Str;
use Laraneat\Modules\Exceptions\ModuleHasNoNamespace;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Module;

abstract class BaseMigrationCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * Whether to require confirmation in production.
     */
    protected bool $requiresConfirmation = true;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->requiresConfirmation && ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        try {
            $modulesToHandle = $this->getModuleArgumentOrFail();
        } catch (ModuleNotFound|ModuleHasNonUniquePackageName|ModuleHasNoNamespace $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($modulesToHandle as $module) {
            $this->line('Running for module: <info>' . $module->getPackageName() . '</info>');
            $this->executeForModule($module);
        }

        return self::SUCCESS;
    }

    /**
     * Execute the migration operation for a single module.
     */
    abstract protected function executeForModule(Module $module): void;

    /**
     * Get migration paths for a module.
     *
     * @return array<int, string>
     */
    protected function getMigrationPaths(Module $module): array
    {
        /** @var Migrator|null $migrator */
        $migrator = $this->laravel['migrator'] ?? null;

        if ($migrator === null) {
            return [];
        }

        return collect($migrator->paths())
            ->filter(fn (string $path) => Str::startsWith($path, $module->getPath()))
            ->values()
            ->toArray();
    }
}
